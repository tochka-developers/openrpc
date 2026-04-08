<?php

namespace Tochka\OpenRpc\Descriptors\Method\Pipes;

use Tochka\OpenRpc\Descriptors\Method\MethodContext;

interface PipeInterface
{
    public function handle(MethodContext $context, callable $next): MethodContext;
}
