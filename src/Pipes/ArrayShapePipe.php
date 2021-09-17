<?php

namespace Tochka\OpenRpc\Pipes;

use Tochka\JsonRpc\Annotations\ApiArrayShape;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Support\JsonRpcDocBlock;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\DTO\References\SchemaReference;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Support\ClassContext;
use Tochka\OpenRpc\Support\Context;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;
use Tochka\OpenRpc\Support\MethodContext;
use Tochka\OpenRpc\Support\StrSupport;
use Tochka\OpenRpc\Support\VirtualContext;

class ArrayShapePipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        // если ArrayShape находится в описании результата метода
        if ($result->context instanceof MethodContext && $result->isResult) {
            $annotation = $this->getAnnotationFromDocBlock(
                JsonRpcDocBlockFactory::makeForMethod(
                    $result->context->getClassName(),
                    $result->context->getMethodName()
                )
            );

            if ($annotation !== null) {
                $result->schema = $this->getSchema($result, $annotation->shape, new VirtualContext());
    
                return $result;
            }
        }
        
        // если ArrayShape находится в описании свойства класса
        if ($result->context instanceof ClassContext && !empty($result->parameter->name)) {
            $annotation = $this->getAnnotationFromDocBlock(
                JsonRpcDocBlockFactory::makeForProperty($result->context->getClassName(), $result->parameter->name)
            );

            if ($annotation !== null) {
                $result->schema = $this->getSchema($result, $annotation->shape, new VirtualContext());
    
                return $result;
            }
        }
    
        // если ArrayShape находится в описании класса
        if ($result->parameter->className !== null) {
            $annotation = $this->getAnnotationFromDocBlock(
                JsonRpcDocBlockFactory::makeForClass($result->parameter->className)
            );

            if ($annotation !== null) {
                $result->schema = $result->schemasDictionary->getReference(
                    StrSupport::fullyQualifiedClassName($result->parameter->className . 'ArrayShape'),
                    SchemaReference::class,
                    fn() => $this->getSchema($result, $annotation->shape, new ClassContext($result->parameter->className))
                );
            }
        }
        
        return $result;
    }
    
    private function getAnnotationFromDocBlock(?JsonRpcDocBlock $docBlock): ?ApiArrayShape
    {
        if ($docBlock !== null) {
            return $docBlock->firstAnnotation(ApiArrayShape::class);
        }
        
        return null;
    }
    
    private function getSchema(ExpectedSchemaPipeObject $expected, array $shape, Context $context): Schema
    {
        $schema = new Schema();
        
        $schema->type = ParameterTypeEnum::TYPE_OBJECT()->toJsonType();
        $schema->properties = $this->getPropertiesFromShape($expected, $shape, $context);
        $schema->required = array_keys($shape);
        
        return $schema;
    }
    
    private function getPropertiesFromShape(ExpectedSchemaPipeObject $expected, array $shape, Context $context): array
    {
        $properties = [];
        
        foreach ($shape as $field => $type) {
            $typeEnum = ParameterTypeEnum::fromVarType($type);
            $parameter = new Parameter($field, $typeEnum);
            
            if (class_exists($type) && $typeEnum->is(ParameterTypeEnum::TYPE_MIXED())) {
                $parameter->className = $type;
                $parameter->type = ParameterTypeEnum::TYPE_OBJECT();
                $parameter->required = true;
            }
            
            $properties[$field] = MethodDescription::getSchemaWithPipes($parameter, $context, $expected->isResult);
        }
        
        return $properties;
    }
}
