<?php

namespace Tochka\OpenRpc;

class VarType
{
    public const TYPE_NULL = 'null';
    public const TYPE_BOOL = 'bool';
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_OBJECT = 'object';
    public const TYPE_MIXED = 'mixed';
    
    public const TYPE_SCHEMA_MAP = [
        self::TYPE_NULL => 'null',
        self::TYPE_BOOL => 'boolean',
        self::TYPE_INT => 'integer',
        self::TYPE_FLOAT => 'number',
        self::TYPE_STRING => 'string',
        self::TYPE_ARRAY => 'array',
        self::TYPE_OBJECT => 'object',
        self::TYPE_MIXED => 'mixin',
    ];
    
    public string $type;
    
    public bool $builtIn = true;
    
    public ?string $className = null;
    
    public bool $isArray = false;
    
    public function __construct(string $type, ?bool $isArray = null)
    {
        $this->type = $type;
        
        if ($isArray !== null) {
            $this->isArray = $isArray;
        } elseif ($this->type === self::TYPE_ARRAY) {
            $this->isArray = true;
        }
        
        if ($this->isArray && $this->type === self::TYPE_ARRAY) {
            $this->type = self::TYPE_MIXED;
        }
        
        if (!array_key_exists($type, self::TYPE_SCHEMA_MAP)) {
            $this->type = $this->isArray ? self::TYPE_MIXED : self::TYPE_OBJECT;
            $this->builtIn = false;
            $this->className = trim($type, '\\');
        }
    }
    
    public function getSchemaType(): string
    {
        return self::TYPE_SCHEMA_MAP[$this->type] ?? 'mixin';
    }
}
