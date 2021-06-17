<?php

namespace Tochka\OpenRpc\Handlers;

use Tochka\OpenRpc\Contracts\OpenRpcHandlerInterface;
use Tochka\OpenRpc\Facades\OpenRpcCache;

class OpenRpcCacheHandler implements OpenRpcHandlerInterface
{
    private OpenRpcHandlerInterface $handler;
    
    public function __construct(OpenRpcHandlerInterface $handler)
    {
        $this->handler = $handler;
    }
    
    public function handle(): array
    {
        $cachedSchema = OpenRpcCache::get();
        
        return $cachedSchema ?? $this->handler->handle();
    }
}
