<?php

namespace Tochka\OpenRpc;

use Tochka\JsonRpc\Router\Router;
use Tochka\OpenRpc\Cache\CacheInterface;
use Tochka\OpenRpc\Handlers\OpenRpcGenerator;
use Tochka\OpenRpc\Support\OpenRpcConfig;

class OpenRpc
{
    protected Router $router;
    protected OpenRpcConfig $config;
    protected ?CacheInterface $cache = null;

    public function __construct(OpenRpcConfig $openRpcConfig, Router $router)
    {
        $this->config = $openRpcConfig;
        $this->router = $router;
        if ($this->config->cacheHandler) {
            if (!is_subclass_of($this->config->cacheHandler, CacheInterface::class)) {
                throw new \TypeError($this->config->cacheHandler . ' must implement ' . CacheInterface::class);
            }
            $this->cache = new $this->config->cacheHandler('openrpc_' . $this->config->serverName);
        }
    }


    /**
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handle(): string
    {
        if ($this->cache) {
            $cacheResult = $this->cache->get();
            if ($cacheResult) {
                return $cacheResult;
            }
        }
        $generator = new OpenRpcGenerator($this->config, $this->router);

        return json_encode($generator->handle());
    }


    /**
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cacheMake(): void
    {
        if (!$this->cache) {
            throw new \RuntimeException('Cache handler not set');
        }
        $generator = new OpenRpcGenerator($this->config, $this->router);

        $this->cache->set($generator->handle());
    }

    public function cacheClear(): void
    {
        if (!$this->cache) {
            throw new \RuntimeException('Cache handler not set');
        }

        $this->cache->clear();
    }
}
