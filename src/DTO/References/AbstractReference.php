<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Support\AliasedDictionary;
use Tochka\OpenRpc\Support\DataTransferObject;
use Tochka\OpenRpc\DTO\Reference;

/**
 * @template T
 */
abstract class AbstractReference extends DataTransferObject
{
    private string $name;
    /** @var AliasedDictionary<T> */
    private AliasedDictionary $dictionary;
    
    /**
     * @param string $name
     * @param AliasedDictionary<T> $dictionary
     */
    public function __construct(string $name, AliasedDictionary $dictionary)
    {
        $this->name = $name;
        $this->dictionary = $dictionary;
    }
    
    /**
     * @return T
     */
    public function getItem(): object
    {
        return $this->getDictionary()->getItem($this->getName());
    }
    
    /**
     * @return AliasedDictionary<T>
     */
    public function getDictionary(): AliasedDictionary
    {
        return $this->dictionary;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function toArray(): array
    {
        return (new Reference(trim($this->getPath(), '/') . '/' . $this->dictionary->getAlias($this->name)))
            ->toArray();
    }
    
    abstract protected function getPath(): string;
}
