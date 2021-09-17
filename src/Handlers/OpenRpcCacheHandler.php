<?php

namespace Tochka\OpenRpc\Handlers;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\OpenRpc\Contracts\OpenRpcHandlerInterface;

class OpenRpcCacheHandler implements OpenRpcHandlerInterface
{
    private OpenRpcHandlerInterface $handler;
    private CacheInterface $cache;
    
    public function __construct(OpenRpcHandlerInterface $handler, CacheInterface $cache)
    {
        $this->handler = $handler;
        $this->cache = $cache;
    }
    
    /**
     * @throws InvalidArgumentException
     */
    public function handle(): array
    {
        if ($this->cache->has('schema')) {
            return $this->cache->get('schema');
        }
        
        return $this->handler->handle();
    }
}
