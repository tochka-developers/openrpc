<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

namespace Tochka\OpenRpc\Tests\Fixtures\Other;

use Tochka\OpenRpc\Attributes\SchemaReference;

#[SchemaReference]
class SharedData
{
    public string $other;
}
