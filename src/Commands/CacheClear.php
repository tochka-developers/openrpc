<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Tochka\JsonRpc\Router\Router;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\OpenRpc\OpenRpc;
use Tochka\OpenRpc\Support\OpenRpcConfig;

class CacheClear extends Command
{
    protected $signature = 'openrpc:clear {server?}';
    protected $description = 'Clear cached OpenRpc schema';
    
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
    
    protected function handleOne(string $serverName): void
    {
        $openRpc = new OpenRpc(
            OpenRpcConfig::makeFromConfigFile($serverName),
            new Router(ServerConfig::makeFromConfigFile($serverName))
        );
        $openRpc->cacheClear();
        $this->info('Server: ' . $serverName . ' OpenRpc cache  cleared!');
    }
}
