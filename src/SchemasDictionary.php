<?php

namespace Tochka\OpenRpc;

use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\DTO\Schema;

class SchemasDictionary
{
    private array $schemas = [];
    
    private array $aliases = [];
    
    public function addSchema(SchemaReferenceInterface $schema, string $className): void
    {
        $this->schemas[$className] = $schema->getSchema();
    }
    
    public function getSchema(string $className): ?Schema
    {
        return $this->schemas[$className] ?? null;
    }
    
    public function getSchemas(): array
    {
        return $this->schemas;
    }
    
    public function getAliasedSchemas(): array
    {
        if (empty($this->aliases)) {
            $this->aliases = $this->generateAliases();
        }
        
        $aliasedSchemas = [];
        
        foreach ($this->schemas as $className => $schema) {
            $aliasedSchemas[$this->getAlias($className)] = $schema;
        }
        
        return $aliasedSchemas;
    }
    
    public function hasSchema(string $className): bool
    {
        return array_key_exists($className, $this->schemas);
    }
    
    public function getAlias(string $className): string
    {
        if (empty($this->aliases)) {
            $this->aliases = $this->generateAliases();
        }
        
        return $this->aliases[$className] ?? $className;
    }
    
    private function generateAliases(): array
    {
        $aliases = [];
        foreach ($this->schemas as $className => $schema) {
            $shortClassName = class_basename($className);
            if (\in_array($shortClassName, $aliases, true)) {
                $i = 1;
                while (in_array($shortClassName . '_' . $i, $aliases, true)) {
                    $i++;
                }
                $shortClassName .= '_' . $i;
            }
            
            $aliases[$className] = $shortClassName;
        }
        
        return $aliases;
    }
}
