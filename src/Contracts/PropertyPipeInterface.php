<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\ExpectedPipeObject;

interface PropertyPipeInterface
{
    public function handle(ExpectedPipeObject $expected, callable $next): ExpectedPipeObject;
}
