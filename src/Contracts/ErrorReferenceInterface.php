<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\Error;

interface ErrorReferenceInterface
{
    public function getError(): Error;
}
