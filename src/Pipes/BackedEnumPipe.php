<?php

namespace Tochka\OpenRpc\Pipes;

use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;
use Tochka\OpenRpc\Support\StrSupport;

class BackedEnumPipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        if (
            $result->parameter->className !== null
            && $result->parameter->type->is(ParameterTypeEnum::TYPE_OBJECT)
            && is_subclass_of($result->parameter->className, '\BackedEnum')
        ) {
            $this->setSchemaEnumProperty($result->schema->getSchema(), $result->parameter->className);
        }
        
        return $result;
    }
    
    /**
     * @param Schema $schema
     * @param class-string<\BackedEnum> $className
     */
    private function setSchemaEnumProperty(Schema $schema, string $className): void
    {
        $schema->enum = array_map(fn($enum) => $enum->value, $className::cases());
        
        $classDocBlock = JsonRpcDocBlockFactory::makeForClass($className);
        if ($classDocBlock !== null) {
            $schema->title = $classDocBlock->getSummary();
            $schema->description = StrSupport::resolveRef($classDocBlock->getDescription());
            
            $reflector = $classDocBlock->getReflector();
            if ($reflector instanceof \ReflectionClass) {
                $schema->oneOf = $this->getConstSchemasForValues($reflector);
            }
        }
        
        if (is_int($className::cases()[0]->value)) {
            $schema->type = ParameterTypeEnum::TYPE_INTEGER()->toJsonType();
        } elseif (is_string($className::cases()[0]->value)) {
            $schema->type = ParameterTypeEnum::TYPE_STRING()->toJsonType();
        } else {
            $schema->type = ParameterTypeEnum::TYPE_MIXED()->toJsonType();
        }
    }
    
    private function getConstSchemasForValues(\ReflectionClass $reflector): array
    {
        $schemas = [];
        
        $constants = $reflector->getReflectionConstants();
        foreach ($constants as $constant) {
            $docBlock = JsonRpcDocBlockFactory::make($constant);
            
            $schema = new Schema();
            $schema->const = $constant->getValue();
            
            if ($docBlock !== null) {
                $schema->description = $docBlock->getSummary();
            }
            
            $schemas[] = $schema;
        }
        
        return $schemas;
    }
}
