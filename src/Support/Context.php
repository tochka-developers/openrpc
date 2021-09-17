<?php

namespace Tochka\OpenRpc\Support;

abstract class Context
{
    private ContextTypeEnum $type;
    
    public function __construct(ContextTypeEnum $type)
    {
        $this->type = $type;
    }
    
    public function getType(): ContextTypeEnum
    {
        return $this->type;
    }
}
