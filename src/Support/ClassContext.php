<?php

namespace Tochka\OpenRpc\Support;

class ClassContext extends Context
{
    private string $className;
    
    public function __construct(string $className)
    {
        parent::__construct(ContextTypeEnum::TYPE_CLASS());
        
        $this->className = $className;
    }
    
    public function getClassName(): string
    {
        return $this->className;
    }
}
