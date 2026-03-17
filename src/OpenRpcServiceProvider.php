<?php

namespace Tochka\OpenRpc;

use Illuminate\Support\ServiceProvider;
use Tochka\OpenRpc\Commands\Cache;
use Tochka\OpenRpc\Commands\CacheClear;

class OpenRpcServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Cache::class, CacheClear::class]);
        }
        
        // Publish configuration
        $this->publishes([__DIR__ . '/../config/openrpc.php' => config_path('openrpc.php')], 'openrpc-config');
    }
}
