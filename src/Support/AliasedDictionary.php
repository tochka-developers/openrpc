<?php

namespace Tochka\OpenRpc\Support;

/**
 * @template T
 */
class AliasedDictionary
{
    /** @var array<string, T> */
    private array $items = [];
    
    /** @var array<string> */
    private array $aliases = [];
    
    /**
     * @param T $instance
     * @param string $name
     */
    public function addItem(object $instance, string $name): void
    {
        $this->items[$name] = $instance;
    }
    
    public function hasItem(string $name): bool
    {
        return array_key_exists($name, $this->items);
    }
    
    /**
     * @param string $name
     * @return T|null
     */
    public function getItem(string $name): ?object
    {
        return $this->items[$name] ?? null;
    }
    
    /**
     * @return array<string, T>
     */
    public function getItems(): array
    {
        return $this->items;
    }
    
    /**
     * @template Y
     * @param string $name
     * @param callable $makeCall
     * @param class-string<Y> $referenceClass
     * @return Y
     */
    public function getReference(string $name, string $referenceClass, callable $makeCall): object
    {
        if (!$this->hasItem($name)) {
            $item = $makeCall();
            $this->addItem($item, $name);
        }
        
        return new $referenceClass($name, $this);
    }
    
    public function getAliasedItems(): array
    {
        if (empty($this->aliases)) {
            $this->aliases = $this->generateAliases();
        }
        
        $aliasedSchemas = [];
        
        foreach ($this->items as $name => $item) {
            $aliasedSchemas[$this->getAlias($name)] = $item;
        }
        
        return $aliasedSchemas;
    }
    
    public function getAlias(string $name): string
    {
        if (empty($this->aliases)) {
            $this->aliases = $this->generateAliases();
        }
        
        return $this->aliases[$name] ?? $name;
    }
    
    private function generateAliases(): array
    {
        $aliases = [];
        foreach ($this->items as $name => $item) {
            $shortClassName = class_basename($name);
            if (\in_array($shortClassName, $aliases, true)) {
                $i = 1;
                while (in_array($shortClassName . '_' . $i, $aliases, true)) {
                    $i++;
                }
                $shortClassName .= '_' . $i;
            }
            
            $aliases[$name] = $shortClassName;
        }
        
        return $aliases;
    }
}
