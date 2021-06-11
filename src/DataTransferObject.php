<?php

namespace Tochka\OpenRpc;

use Illuminate\Support\Arr;

class DataTransferObject
{
    protected array $exceptKeys = [];
    protected array $onlyKeys = [];
    
    public function all(): array
    {
        $data = [];
    
        $class = new \ReflectionClass(static::class);
    
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
    
        foreach ($properties as $reflectionProperty) {
            // Skip static properties and not initialized properties
            if ($reflectionProperty->isStatic() || !$reflectionProperty->isInitialized($this)) {
                continue;
            }
        
            $data[$reflectionProperty->getName()] = $reflectionProperty->getValue($this);
        }
    
        return $data;
    }
    
    /**
     * @param string ...$keys
     *
     * @return static
     */
    public function only(string ...$keys): self
    {
        $dataTransferObject = clone $this;
        
        $dataTransferObject->onlyKeys = [...$this->onlyKeys, ...$keys];
        
        return $dataTransferObject;
    }
    
    /**
     * @param string ...$keys
     *
     * @return static
     */
    public function except(string ...$keys): self
    {
        $dataTransferObject = clone $this;
        
        $dataTransferObject->exceptKeys = [...$this->exceptKeys, ...$keys];
        
        return $dataTransferObject;
    }
    
    public function toArray(): array
    {
        if (count($this->onlyKeys)) {
            $array = Arr::only($this->all(), $this->onlyKeys);
        } else {
            $array = Arr::except($this->all(), $this->exceptKeys);
        }
        
        return $this->parseArray($array);
    }
    
    protected function parseArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
                
                continue;
            }
            
            if (!is_array($value)) {
                continue;
            }
            
            $array[$key] = $this->parseArray($value);
        }
        
        return $array;
    }
}
