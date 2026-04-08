<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Router\Router;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\OpenRpc\OpenRpc;
use Tochka\OpenRpc\Support\OpenRpcConfig;

class Cache extends Command
{
    protected $signature = 'openrpc:cache {server?}';
    protected $description = 'Make and cache OpenRpc schema';
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        $serverName = $this->argument('server');
        if ($serverName) {
            $this->handleOne($serverName);
        } else {
            $configs = Config::get('openrpc', []);
            foreach ($configs as $name => $_) {
                $this->handleOne($name);
            }
        }
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    protected function handleOne(string $serverName): void
    {
        $openRpc = new OpenRpc(
            OpenRpcConfig::makeFromConfigFile($serverName),
            new Router(ServerConfig::makeFromConfigFile($serverName))
        );
        $openRpc->cacheClear();
        $this->info('Server: ' . $serverName . ' OpenRpc cache  cleared!');
        $openRpc->cacheMake();
        $this->info('Server: ' . $serverName . ' OpenRpc cached successfully!');
    }
}
