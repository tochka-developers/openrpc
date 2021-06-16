<?php

namespace Tochka\OpenRpc;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Facades\OpenRpc;
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
            OpenRpc::class,
            function () {
                $openRpcConfig = Config::get('openrpc', []);
                $jsonRpcConfig = Config::get('jsonrpc', []);
                
                return new OpenRpcGenerator($openRpcConfig, $jsonRpcConfig);
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
        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/openrpc.php' => config_path('openrpc.php')], 'openrpc-config');
    }
}
