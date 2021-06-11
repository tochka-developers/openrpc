<?php

namespace Tochka\OpenRpc;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory;
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
                AnnotationReader::addGlobalIgnoredName('mixin');
                $reader = new CachedReader(
                    new AnnotationReader(),
                    new PhpFileCache($this->app->bootstrapPath('cache/annotations'), '.annotations.php'),
                    Config::get('app.debug')
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
}
