<?php

namespace Tochka\OpenRpc\Pipes;

use Illuminate\Database\Eloquent\Model;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\ExpectedPipeObject;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\VarType;

class ModelPipe implements PropertyPipeInterface
{
    /**
     * @throws \ReflectionException
     */
    public function handle(ExpectedPipeObject $expected, callable $next): ExpectedPipeObject
    {
        $result = $next($expected);
        
        $schema = $expected->schema->getSchema();
        
        if (
            !$schema instanceof Schema
            || $expected->varType === null
            || $expected->varType->builtIn
            || !is_subclass_of($expected->varType->className, Model::class)
        ) {
            return $result;
        }
        
        $reflectionClass = new \ReflectionClass($expected->varType->className);
        $classPropertyTags = MethodDescription::getPropertyTagsFromDTO($reflectionClass);
        $defaultProperties = $reflectionClass->getDefaultProperties();
        
        $properties = [];
        foreach ($classPropertyTags as $tag) {
            if ($this->isHiddenAttribute($defaultProperties, $tag->getVariableName())) {
                continue;
            }
            if (array_key_exists($tag->getVariableName(), $schema->properties)) {
                $schemaReference = $schema->properties[$tag->getVariableName()];
                $dateFormat = $this->dateAttribute($defaultProperties, $tag->getVariableName());
                if ($dateFormat !== null) {
                    $attributeSchema = new Schema();
                    $attributeSchema->type = VarType::TYPE_STRING;
                    $attributeSchema->format = $dateFormat;
                    $description = $tag->getDescription();
                    if ($description !== null) {
                        set_field_if_not_empty($description->getBodyTemplate(), $attributeSchema, 'title');
                        set_field_if_not_empty($description->getBodyTemplate(), $attributeSchema, 'description');
                    }
                    
                    $properties[$tag->getVariableName()] = $attributeSchema;
                } else {
                    $properties[$tag->getVariableName()] = $schemaReference;
                }
            }
        }
        
        $schema->properties = $properties;
        
        return $result;
    }
    
    private function isHiddenAttribute(array $defaultProperties, string $attributeName): bool
    {
        if (!empty($defaultProperties['visible']) && !\in_array($attributeName, $defaultProperties['visible'], true)) {
            return true;
        }
        if (in_array($attributeName, $defaultProperties['hidden'] ?? [], true)) {
            return true;
        }
        
        return false;
    }
    
    private function dateAttribute(array $defaultProperties, string $attributeName): ?string
    {
        $defaultFormat = 'Y-m-d H:i:s';
        $dates = $defaultProperties['dates'] ?? [];
        $casts = $defaultProperties['casts'] ?? [];
        
        if (\in_array($attributeName, $dates, true)) {
            return $defaultFormat;
        }
        
        if (array_key_exists($attributeName, $casts)) {
            $cast = $casts[$attributeName];
            $type = explode(':', $cast);
            if ($type[0] === 'date' || $type[0] === 'datetime' || $type[0] === 'time' || $type[0] === 'timestamp') {
                return count($type) > 1 ? $type[1] : $defaultFormat;
            }
        }
        
        return null;
    }
}
