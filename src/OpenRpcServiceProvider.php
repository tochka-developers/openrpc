<?php

namespace Tochka\OpenRpc;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;
use Tochka\OpenRpc\Commands\Cache;
use Tochka\OpenRpc\Commands\CacheClear;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Facades\OpenRpc;
use Tochka\OpenRpc\Facades\OpenRpcCache;
use Tochka\OpenRpc\Handlers\OpenRpcCacheHandler;
use Tochka\OpenRpc\Handlers\OpenRpcGenerator;
use Tochka\OpenRpc\Pipes\ArrayShapePipe;
use Tochka\OpenRpc\Pipes\EnumPipe;
use Tochka\OpenRpc\Pipes\ExpectedValuesPipe;
use Tochka\OpenRpc\Pipes\ModelPipe;
use Tochka\OpenRpc\Pipes\ValueExamplePipe;

class OpenRpcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            OpenRpcCache::class,
            function () {
                return new SchemaCache($this->app->bootstrapPath('cache/openrpc'));
            }
        );
        
        $this->app->singleton(
            OpenRpc::class,
            function () {
                $openRpcConfig = Config::get('openrpc', []);
                $jsonRpcConfig = Config::get('jsonrpc', []);
                $handler = new OpenRpcGenerator($openRpcConfig, $jsonRpcConfig);
                
                return new OpenRpcCacheHandler($handler);
            }
        );
        
        $this->app->singleton(
            MethodDescription::class,
            function () {
                //AnnotationReader::addGlobalIgnoredName('mixin');
                
                $reader = new MergeReader(
                    [
                        new AnnotationReader(),
                        new AttributeReader(),
                    ]
                );
                
                $docBlockFactory = DocBlockFactory::createInstance();
                
                $finder = new ControllerFinder();
                
                $instance = new MethodDescriptionGenerator($reader, $docBlockFactory, $finder);
                $instance->addPipe(new ArrayShapePipe($reader, $docBlockFactory));
                $instance->addPipe(new ExpectedValuesPipe($reader));
                $instance->addPipe(new ValueExamplePipe($reader));
                $instance->addPipe(new EnumPipe($docBlockFactory));
                $instance->addPipe(new ModelPipe());
                
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
        
        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/openrpc.php' => config_path('openrpc.php')], 'openrpc-config');
        
        // add routes
        $routePath = Config::get('openrpc.endpoint', '/api/openrpc.json');
        Route::put(
            $routePath,
            static function () {
                return OpenRpc::handle();
            }
        );
    }
}
