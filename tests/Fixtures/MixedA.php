<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\SchemaReference;

#[SchemaReference]
class MixedA
{
    public MixedB $child;
}
