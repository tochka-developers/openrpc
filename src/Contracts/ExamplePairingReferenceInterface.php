<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\ExamplePairing;

interface ExamplePairingReferenceInterface
{
    public function getExamplePairing(): ExamplePairing;
}
