<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Attributes\SchemaReference;

#[SchemaReference]
class SharedData
{
    /**
     * Agreement
     *
     * Consent description.
     * @var bool|null
     */
    #[Examples([null, true, false, 0, [30, 45]])]
    public ?bool $agree = null;
}
