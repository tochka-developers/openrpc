<?php

namespace Tochka\OpenRpc\Pipes;

use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\DTO\SchemaReference;
use Tochka\OpenRpc\ExpectedPipeObject;
use Tochka\OpenRpc\VarType;
use BenSampo\Enum\Enum;
use phpDocumentor\Reflection\DocBlockFactory;

class EnumPipe implements PropertyPipeInterface
{
    private DocBlockFactory $docBlockFactory;
    
    public function __construct(DocBlockFactory $docBlockFactory)
    {
        $this->docBlockFactory = $docBlockFactory;
    }
    
    /**
     * @throws \ReflectionException
     */
    public function handle(ExpectedPipeObject $expected, callable $next): ExpectedPipeObject
    {
        if (
            $expected->schema instanceof Schema
            && $expected->varType !== null
            && !$expected->varType->builtIn
            && is_subclass_of($expected->varType->className, Enum::class)
        ) {
            if (!$expected->schemasDictionary->hasSchema($expected->varType->className)) {
                $expected->schemasDictionary->addSchema(
                    $this->getSchemaEnumProperty($expected->schema, $expected->varType->className),
                    $expected->varType->className
                );
            }
            
            if ($expected->varType->isArray) {
                $expected->schema->items = new SchemaReference($expected->varType->className, $expected->schemasDictionary);
            } else {
                $expected->schema = new SchemaReference($expected->varType->className, $expected->schemasDictionary);
            }
            
            return $expected;
        }
        
        return $next($expected);
    }
    
    /**
     * @param Schema $schema
     * @param class-string $className
     *
     * @return Schema
     * @throws \ReflectionException
     */
    private function getSchemaEnumProperty(Schema $schema, string $className): Schema
    {
        /** @var Enum $className */
        $schema->enum = $className::getValues();
    
        set_title_and_description_from_class($schema, $className, $this->docBlockFactory);
        
        $type = array_reduce(
            $schema->enum,
            function ($carry, $item) {
                switch (gettype($item)) {
                    case 'string':
                        return ($carry === null || $carry === VarType::TYPE_STRING) ? VarType::TYPE_STRING : VarType::TYPE_MIXED;
                    case 'int':
                        return ($carry === null || $carry === VarType::TYPE_INT) ? VarType::TYPE_INT : VarType::TYPE_MIXED;
                    case 'float':
                        return ($carry === null || $carry === VarType::TYPE_FLOAT) ? VarType::TYPE_FLOAT : VarType::TYPE_MIXED;
                    case 'bool':
                        return ($carry === null || $carry === VarType::TYPE_BOOL) ? VarType::TYPE_BOOL : VarType::TYPE_MIXED;
                    default:
                        return VarType::TYPE_MIXED;
                }
            }
        );
        
        $schema->type = VarType::TYPE_SCHEMA_MAP[$type] ?? VarType::TYPE_MIXED;
        
        return $schema;
    }
}
