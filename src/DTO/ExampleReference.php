<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ExampleReferenceInterface;

class ExampleReference extends Reference implements ExampleReferenceInterface
{
    public function getExample(): ?Example
    {
        return new Example();
    }
}
