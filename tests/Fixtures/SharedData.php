<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;

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
