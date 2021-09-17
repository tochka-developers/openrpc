<?php

namespace Tochka\OpenRpc\Support;

class MethodContext extends Context
{
    private string $className;
    private string $methodName;
    
    public function __construct(string $className, string $methodName)
    {
        parent::__construct(ContextTypeEnum::TYPE_METHOD());
        $this->className = $className;
        $this->methodName = $methodName;
    }
    
    public function getClassName(): string
    {
        return $this->className;
    }
    
    public function getMethodName(): string
    {
        return $this->methodName;
    }
}
