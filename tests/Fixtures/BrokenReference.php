<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

class BrokenReference
{
    public InlineCycle $child;
}
