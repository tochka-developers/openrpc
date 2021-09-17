<?php

namespace Tochka\OpenRpc\Pipes;

use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;

class ModelPipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        if (!is_subclass_of($result->parameter->className, '\Illuminate\Database\Eloquent\Model')) {
            return $result;
        }
        
        $docBlock = JsonRpcDocBlockFactory::makeForClass($result->parameter->className);
        if ($docBlock === null) {
            return $result;
        }
        
        $schema = $result->schema->getSchema();
        $schema->type = ParameterTypeEnum::TYPE_OBJECT()->toJsonType();
        $schema->required = [];
        $schema->properties = [];
        
        /** @var Property[]|PropertyWrite[]|PropertyRead[] $tags */
        $tags = $docBlock->getTags(null,
            fn($tag) => $tag instanceof Property
                || $tag instanceof PropertyRead
                || $tag instanceof PropertyWrite
        );
        
        $reflector = $docBlock->getReflector();
        if (!$reflector instanceof \ReflectionClass) {
            return $result;
        }
        
        $defaultProperties = $reflector->getDefaultProperties();
        
        foreach ($tags as $tag) {
            if ($this->isHiddenAttribute($defaultProperties, $tag->getVariableName())) {
                continue;
            }
            
            if (!in_array($tag->getVariableName(), $schema->required, true)) {
                $schema->required[] = $tag->getVariableName();
            }
            $type = ParameterTypeEnum::fromVarType($tag->getType());
            
            $parameter = new Parameter($tag->getVariableName(), $type);
            
            if ($type->is(ParameterTypeEnum::TYPE_MIXED) && class_exists($tag->getType())) {
                $parameter->type = ParameterTypeEnum::TYPE_OBJECT();
                $parameter->className = $tag->getType();
            }
            
            if ($tag->getDescription() !== null) {
                $parameter->description = $tag->getDescription()->getBodyTemplate();
            }
            
            $dateFormat = $this->dateAttribute($defaultProperties, $tag->getVariableName());
            if ($dateFormat !== null) {
                $schemaProperty = new Schema();
                $schemaProperty->type = ParameterTypeEnum::TYPE_STRING()->toJsonType();
                $schemaProperty->format = $dateFormat;
                if ($tag->getDescription() !== null) {
                    $schemaProperty->title = $tag->getDescription()->getBodyTemplate();
                    $schemaProperty->description = $tag->getDescription()->getBodyTemplate();
                }
            } else {
                $schemaProperty = MethodDescription::getSchemaWithPipes(
                    $parameter,
                    $result->context,
                    $result->isResult
                );
            }
            
            $schema->properties[$tag->getVariableName()] = $schemaProperty;
        }
        
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
