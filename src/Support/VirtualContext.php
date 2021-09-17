<?php

namespace Tochka\OpenRpc\Support;

class VirtualContext extends Context
{
    public function __construct()
    {
        parent::__construct(ContextTypeEnum::TYPE_VIRTUAL());
    }
}
