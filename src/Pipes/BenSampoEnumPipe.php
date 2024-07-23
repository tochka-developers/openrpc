<?php

namespace Tochka\OpenRpc\Pipes;

use BenSampo\Enum\Enum;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;
use Tochka\OpenRpc\Support\StrSupport;

class BenSampoEnumPipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        if (
            $result->parameter->className !== null
            && $result->parameter->type->is(ParameterTypeEnum::TYPE_OBJECT)
            && is_subclass_of($result->parameter->className, Enum::class)
        ) {
            $this->setSchemaEnumProperty($result->schema->getSchema(), $result->parameter->className);
        }
        
        return $result;
    }
    
    /**
     * @param Schema $schema
     * @param class-string<Enum> $className
     */
    private function setSchemaEnumProperty(Schema $schema, string $className): void
    {
        /** @var Enum $className */
        $schema->enum = $className::getValues();
        
        $classDocBlock = JsonRpcDocBlockFactory::makeForClass($className);
        if ($classDocBlock !== null) {
            $schema->title = $classDocBlock->getSummary();
            $schema->description = StrSupport::resolveRef($classDocBlock->getDescription());
            
            $reflector = $classDocBlock->getReflector();
            if ($reflector instanceof \ReflectionClass) {
                $schema->oneOf = $this->getConstSchemasForValues($reflector);
            }
        }
        
        /** @var ParameterTypeEnum $type */
        $type = array_reduce(
            $schema->enum,
            function (?ParameterTypeEnum $carry, $item) {
                switch (gettype($item)) {
                    case 'string':
                        return ($carry === null || $carry->is(ParameterTypeEnum::TYPE_STRING()))
                            ? ParameterTypeEnum::TYPE_STRING()
                            : ParameterTypeEnum::TYPE_MIXED();
                    case 'integer':
                        return ($carry === null || $carry->is(ParameterTypeEnum::TYPE_INTEGER()))
                            ? ParameterTypeEnum::TYPE_INTEGER()
                            : ParameterTypeEnum::TYPE_MIXED();
                    case 'double':
                        return ($carry === null || $carry->is(ParameterTypeEnum::TYPE_FLOAT()))
                            ? ParameterTypeEnum::TYPE_FLOAT()
                            : ParameterTypeEnum::TYPE_MIXED();
                    case 'boolean':
                        return ($carry === null || $carry->is(ParameterTypeEnum::TYPE_BOOLEAN()))
                            ? ParameterTypeEnum::TYPE_BOOLEAN()
                            : ParameterTypeEnum::TYPE_MIXED();
                    default:
                        return ParameterTypeEnum::TYPE_MIXED();
                }
            },
            null
        );
        
        $schema->type = $type->toJsonType();
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
