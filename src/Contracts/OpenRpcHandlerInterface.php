<?php

namespace Tochka\OpenRpc\Contracts;

interface OpenRpcHandlerInterface
{
    public function handle(): array;
}
