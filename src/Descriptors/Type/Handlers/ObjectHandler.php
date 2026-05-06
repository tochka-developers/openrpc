<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
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

        $props = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $phpDocType = null;
            $tag = null;
            $docComment = $prop->getDocComment();
            if ($docComment) {
                $docBlock = DocBlockFactory::createInstance()->create($docComment);
                /** @var Var_ $tag */
                $tag = $docBlock->getTagsByName('var')[0] ?? null;
                $phpDocType = $tag?->getType();
            }

            $result = $typeDescriptor->describe($prop->getType(), new Schema(), $phpDocType);

            if ($tag) {
                $result->title = $tag->getDescription()?->__toString();
            }

            if (!$prop->hasDefaultValue()) {
                $schema->required[] = $prop->getName();
            }

            $schema->properties[$prop->name] = $result;
        }

        return $schema;
    }
}
