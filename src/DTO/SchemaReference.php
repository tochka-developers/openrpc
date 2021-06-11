<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\SchemasDictionary;

class SchemaReference extends Reference implements SchemaReferenceInterface
{
    private SchemasDictionary $schemasDictionary;
    private string $className;
    
    public function __construct(string $className, SchemasDictionary $schemasDictionary)
    {
        $this->schemasDictionary = $schemasDictionary;
        $this->className = $className;
        
        parent::__construct('#/components/schemas/' . $className);
    }
    
    public function getSchema(): ?Schema
    {
        return $this->schemasDictionary->getSchema($this->className);
    }
    
    public function toArray(): array
    {
        return [
            '$ref' => '#/components/schemas/' . $this->schemasDictionary->getAlias($this->className),
        ];
    }
}
