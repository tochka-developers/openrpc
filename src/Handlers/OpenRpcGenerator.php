<?php

namespace Tochka\OpenRpc\Handlers;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\Router;
use Tochka\OpenRpc\Contracts\OpenRpcHandlerInterface;
use Tochka\OpenRpc\Descriptors\Method\MethodContext;
use Tochka\OpenRpc\Descriptors\Method\Pipes\PipeInterface;
use Tochka\OpenRpc\Descriptors\Type\Handlers\HandlerInterface;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\OpenRpc;
use Tochka\OpenRpc\Support\OpenRpcConfig;

class OpenRpcGenerator implements OpenRpcHandlerInterface
{
    public const OPEN_RPC_VERSION = '1.2.6';
    
    private OpenRpcConfig $openRpcConfig;
    private Router $router;
    private Pipeline $methodPipeline;
    private TypeDescriptor $typeDescriptor;
    
    public function __construct(OpenRpcConfig $openRpcConfig, Router $router)
    {
        $this->openRpcConfig = $openRpcConfig;
        $this->router = $router;
        
        $this->methodPipeline = App::make(Pipeline::class);
        foreach ($this->openRpcConfig->methodPipes as $class) {
            if (!is_subclass_of($class, PipeInterface::class)) {
                throw new \TypeError($class . ' must implement ' . PipeInterface::class);
            }
            $this->methodPipeline->pipe(new $class());
        }
        
        $this->typeDescriptor = new TypeDescriptor();
        foreach ($this->openRpcConfig->typeDescriptors as $class) {
            if (!is_subclass_of($class, HandlerInterface::class)) {
                throw new \TypeError($class . ' must implement ' . HandlerInterface::class);
            }
            $this->typeDescriptor->addHandler(new $class());
        }
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function handle(): array
    {
        $openRpc = new OpenRpc(self::OPEN_RPC_VERSION, $this->openRpcConfig->info);
        $openRpc->servers[] = $this->openRpcConfig->server;
        $openRpc = $this->routesDescribe($openRpc, $this->router);
        
        return (array)$openRpc;
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    protected function routesDescribe(OpenRpc $openRpc, Router $router): OpenRpc
    {
        /** @var Route $route $route */
        foreach ($router->getAll() as $route) {
            $context = new MethodContext($this->methodPipeline, $route, $this->typeDescriptor);
            $context->describeMethod();
            $context->describeResult();
            $context->describeParameters();
            
            $openRpc->methods[] = $context->getResult();
        }
        
        return $openRpc;
    }
}
