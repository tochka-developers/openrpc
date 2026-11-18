<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

class InlineCycle
{
    public ?InlineCycle $child;
}
