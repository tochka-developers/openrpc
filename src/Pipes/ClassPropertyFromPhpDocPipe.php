<?php

namespace Tochka\OpenRpc\Pipes;

use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\DTO\References\SchemaReference;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;
use Tochka\OpenRpc\Support\StrSupport;

class ClassPropertyFromPhpDocPipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        if ($result->parameter->className === null) {
            return $result;
        }
        
        $docBlock = JsonRpcDocBlockFactory::makeForClass($result->parameter->className);
        if ($docBlock === null) {
            return $result;
        }
        
        $schemaReference = $result->schemasDictionary->getReference(
            StrSupport::fullyQualifiedClassName($expected->parameter->className),
            SchemaReference::class,
            function () {
                $schema = new Schema();
                $schema->type = ParameterTypeEnum::TYPE_OBJECT()->toJsonType();
                
                return $schema;
            }
        );
        
        $schema = $schemaReference->getSchema();
        
        /** @var Property[]|PropertyWrite[]|PropertyRead[] $tags */
        $tags = $docBlock->getTags(null,
            fn($tag) => $tag instanceof Property
                || $tag instanceof PropertyRead
                || $tag instanceof PropertyWrite
        );
        
        foreach ($tags as $tag) {
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
            
            $schema->properties[$tag->getVariableName()] = MethodDescription::getSchemaWithPipes(
                $parameter,
                $result->context,
                $result->isResult
            );
        }
        
        return $result;
    }
}
