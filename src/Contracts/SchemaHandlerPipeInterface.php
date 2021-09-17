<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;

interface SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject;
}
