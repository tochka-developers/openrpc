<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\Link;

interface LinkReferenceInterface
{
    public function getLink(): Link;
}
