<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\Example;

interface ExampleReferenceInterface
{
    public function getExample(): Example;
}
