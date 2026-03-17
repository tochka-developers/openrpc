<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class ModelHandler implements HandlerInterface
{

    public function shouldHandle(SingularTypeInfo $info): bool
    {
        return $info->className && is_subclass_of($info->className, 'Illuminate\Database\Eloquent\Model');
    }

    /**
     * @throws \ReflectionException
     */
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        $reflector = new \ReflectionClass($info->className);
        $docText = $reflector->getDocComment();
        if (!$docText) {
            return $schema;
        }
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createFromReflector($reflector);
        $docBlock = DocBlockFactory::createInstance()->create($docText, $context);
        $properties = $this->filterTags($docBlock->getTags());

        $model = new $info->className();
        $hidden = $model->getHidden();
        foreach ($properties as $property) {
            $name = $property->getVariableName();
            // skip hidden fields
            if (\in_array($name, $hidden)) {
                continue;
            }
            $result = $typeDescriptor->describe(null, new Schema(), $property->getType());

            $schema->properties[$property->getVariableName()] = $result;
            if ($property instanceof Property) {
                $schema->required[] = $property->getVariableName();
            }
        }

        return $schema;
    }

    /**
     * @param array<int, Tag> $tags
     *
     * @return array<int, Property|PropertyRead>
     */
    protected function filterTags(array $tags): array
    {
        $result = [];

        foreach ($tags as $tag) {
            if (!\in_array($tag->getName(), ['property', 'property-read'])) {
                continue;
            }

            $result[] = $tag;
        }

        return $result;
    }
}
