<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Attributes\Optional;
use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class ObjectHandler implements HandlerInterface
{

    public function shouldHandle(SingularTypeInfo $info): bool
    {
        return $info->className && class_exists($info->className);
    }

    /**
     * @throws \ReflectionException
     */
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        $reflector = new \ReflectionClass($info->className);

        $schema->properties = [];
        $props = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $phpDocType = null;
            $tag = null;
            $docBlock = null;
            $docComment = $prop->getDocComment();
            if ($docComment) {
                $context = (new ContextFactory())->createFromReflector($prop->getDeclaringClass());
                $docBlock = DocBlockFactory::createInstance()->create($docComment, $context);
                /** @var Var_ $tag */
                $tag = $docBlock->getTagsByName('var')[0] ?? null;
                $phpDocType = $tag?->getType();
            }

            $result = $typeDescriptor->describeProperty($prop, $phpDocType);
            $isOptional = $prop->getAttributes(Optional::class) !== [];
            if ($isOptional) {
                $result = $this->withoutNull($result);
            }

            $summary = $docBlock?->getSummary() ?: $tag?->getDescription()?->__toString();
            if ($summary) {
                $result->title = $summary;
                $result->summary = $summary;
            }
            $description = $docBlock?->getDescription()->render();
            if ($description) {
                $result->description = $description;
            }
            $hasDefault = $prop->hasDefaultValue();
            $default = $hasDefault ? $prop->getDefaultValue() : null;
            if ($prop->isPromoted()) {
                foreach ($prop->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $parameter) {
                    if ($parameter->getName() === $prop->getName() && $parameter->isDefaultValueAvailable()) {
                        $hasDefault = true;
                        $default = $parameter->getDefaultValue();
                    }
                }
            }
            if ($hasDefault && (!$isOptional || $default !== null)) {
                $result->default = $default;
            } elseif (!$hasDefault && !$isOptional) {
                $schema->required[] = $prop->getName();
            }
            $examples = $prop->getAttributes(Examples::class)[0] ?? null;
            if ($examples !== null) {
                $result->examples = $examples->newInstance()->values;
            }

            $schema->properties[$prop->name] = $result;
        }

        return $schema;
    }

    protected function withoutNull(Schema $schema): Schema
    {
        $result = clone $schema;
        $result->type = array_values(array_diff($result->type, ['null', 'void']));
        if ($result->type === []) {
            throw new \RuntimeException('Свойство с #[Optional] должно иметь тип, отличный от null.');
        }
        if (isset($result->anyOf)) {
            $result->anyOf = [];
            foreach ($schema->anyOf as $branch) {
                if (array_diff($branch->type, ['null', 'void']) !== []) {
                    $result->anyOf[] = $this->withoutNull($branch);
                }
            }
            if ($result->anyOf === []) {
                throw new \RuntimeException('Свойство с #[Optional] должно иметь тип, отличный от null.');
            }
            if (count($result->anyOf) === 1) {
                $single = clone $result->anyOf[0];
                foreach (get_object_vars($result) as $name => $value) {
                    if ($name === 'anyOf' || ($name === 'type' && $value === ['mixed']) || ($name === 'required' && $value === [])) {
                        continue;
                    }
                    $single->{$name} = $value;
                }

                return $single;
            }
        }

        return $result;
    }
}
