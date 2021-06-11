<?php

namespace Tochka\OpenRpc;

use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use phpDocumentor\Reflection\DocBlock;

class ExpectedPipeObject
{
    public ?\ReflectionMethod $method = null;
    public ?\ReflectionProperty $property = null;
    
    public SchemaReferenceInterface $schema;
    public SchemasDictionary $schemasDictionary;
    
    public VarType $varType;
    
    public ?DocBlock $docBlock;
    
    public function __construct(
        ?\Reflector $reflector,
        SchemaReferenceInterface $schema,
        VarType $varType,
        SchemasDictionary $schemas,
        ?DocBlock $docBlock
    ) {
        $this->varType = $varType;
        $this->schema = $schema;
        if ($reflector instanceof \ReflectionMethod) {
            $this->method = $reflector;
        }
        if ($reflector instanceof \ReflectionProperty) {
            $this->property = $reflector;
        }
        $this->schemasDictionary = $schemas;
        $this->docBlock = $docBlock;
    }
}
