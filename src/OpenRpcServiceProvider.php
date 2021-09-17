<?php

namespace Tochka\OpenRpc;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\SimpleCache\CacheInterface;
use Tochka\JsonRpc\Facades\JsonRpcRouter;
use Tochka\JsonRpc\Helpers\ArrayFileCache;
use Tochka\JsonRpc\Route\JsonRpcRoute;
use Tochka\OpenRpc\Commands\Cache;
use Tochka\OpenRpc\Commands\CacheClear;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Facades\OpenRpc;
use Tochka\OpenRpc\Handlers\OpenRpcCacheHandler;
use Tochka\OpenRpc\Handlers\OpenRpcGenerator;
use Tochka\OpenRpc\Pipes\ArrayShapePipe;
use Tochka\OpenRpc\Pipes\ClassPropertyFromPhpDocPipe;
use Tochka\OpenRpc\Pipes\EnumPipe;
use Tochka\OpenRpc\Pipes\ExpectedValuesPipe;
use Tochka\OpenRpc\Pipes\ModelPipe;
use Tochka\OpenRpc\Pipes\ValueExamplePipe;

class OpenRpcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('OpenRpcCache', function () {
            return new ArrayFileCache('openrpc');
        });
        
        $this->app->singleton(
            OpenRpc::class,
            function () {
                $openRpcConfig = Config::get('openrpc', []);
                $jsonRpcConfig = Config::get('jsonrpc', []);
                $handler = new OpenRpcGenerator($openRpcConfig, $jsonRpcConfig);
                
                /** @var CacheInterface $cache */
                $cache = $this->app->make('OpenRpcCache');
                
                return new OpenRpcCacheHandler($handler, $cache);
            }
        );
        
        $this->app->singleton(
            MethodDescription::class,
            function () {
                $instance = new MethodDescriptionGenerator($this->app);
                $instance->addPipe(new ExpectedValuesPipe());
                $instance->addPipe(new ValueExamplePipe());
                $instance->addPipe(new ArrayShapePipe());
                $instance->addPipe(new EnumPipe());
                $instance->addPipe(new ModelPipe());
                $instance->addPipe(new ClassPropertyFromPhpDocPipe());
    
                return $instance;
            }
        );
    }
    
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
        
        // Add Http Route
        $routePath = Config::get('openrpc.endpoint', '/api/openrpc.json');
        Route::get(
            $routePath,
            static function () {
                return OpenRpc::handle();
            }
        );
        
        // Add JsonRpc route for autodiscovery
        $route = new JsonRpcRoute('default', 'rpc.discover');
        $route->controllerClass = AutoDiscoverController::class;
        $route->controllerMethod = 'discover';
        JsonRpcRouter::add($route);
    }
}
