<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

class ReferenceCycle
{
    public ?ReferenceCycle $child;
}
