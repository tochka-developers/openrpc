<?php

namespace Tochka\OpenRpc\Descriptors\Method;

use Illuminate\Pipeline\Pipeline;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\Route;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\ContentDescriptor;
use Tochka\OpenRpc\DTO\MethodDescriptor;
use Tochka\OpenRpc\DTO\ParameterDescriptor;
use Tochka\OpenRpc\DTO\ResultDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class MethodContext
{
    protected Pipeline $pipeline;
    public readonly Route $route;
    public readonly DocBlock $docBlock;
    public readonly \ReflectionMethod $reflectionMethod;
    public MethodDescriptor|ResultDescriptor|ParameterDescriptor $descriptor;
    protected MethodDescriptor $result;
    /** @var array<\ReflectionParameter> */
    protected readonly array $paramsReflectors;
    protected readonly TypeDescriptor $typeDescriptor;
    
    /**
     * @throws \ReflectionException
     */
    public function __construct(
        Pipeline $pipeline,
        Route $route,
        TypeDescriptor $typeDescriptor,
    ) {
        $this->pipeline = $pipeline;
        $this->route = $route;
        $this->typeDescriptor = $typeDescriptor;
        $this->result = new MethodDescriptor($this->route->name);
        $this->reflectionMethod = new \ReflectionMethod($route->controllerClass, $route->controllerMethod);
        // docblock
        $comment = $this->reflectionMethod->getDocComment();
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createFromReflector($this->reflectionMethod);
        $this->docBlock = DocBlockFactory::createInstance()->create(is_string($comment) ? $comment : '/**  */', $context);
        
        $this->paramsReflectors = collect($this->reflectionMethod->getParameters())->keyBy(
            fn(\ReflectionParameter $param) => $param->getName()
        )->toArray();
    }
    
    public function describeMethod(): void
    {
        $this->descriptor = new MethodDescriptor($this->route->name);
        /** @var self $result */
        $result = $this->pipeline->send($this)->thenReturn();
        $this->result = $result->descriptor;
    }
    
    public function describeResult(): void
    {
        $this->descriptor = new ResultDescriptor($this->route->name . '_return', new Schema());
        $result = $this->pipeline->send($this)->thenReturn();
        $this->result->result = $result->descriptor;
    }
    
    public function describeParameters(): void
    {
        foreach ($this->route->getParams() as $param) {
            if ($param->propType === PropType::DI) {
                continue;
            }
            $this->descriptor = new ParameterDescriptor($param->name, new Schema());
            /** @var MethodContext $parameterResult */
            $parameterResult = $this->pipeline->send($this)->thenReturn();
            // if this RequestObject all property of this object is params
            if ($param->propType === PropType::RequestObject) {
                foreach ($parameterResult->descriptor->schema->properties as $name => $innerProp) {
                    $contentDescriptor = new ContentDescriptor($name, $innerProp);
                    $contentDescriptor->summary = $innerProp->summary ?? '';
                    $this->result->params[] = $contentDescriptor;
                }
                break;
            } else {
                $this->result->params[] = $parameterResult->descriptor;
            }
        }
    }
    
    public function describeType(?\ReflectionType $type, Schema $schema, ?Type $phpDocType = null): Schema
    {
        return $this->typeDescriptor->describe($type, $schema, $phpDocType);
    }
    
    public function getParameterReflector(string $name): ?\ReflectionParameter
    {
        return $this->paramsReflectors[$name] ?? null;
    }
    
    public function getResult(): MethodDescriptor
    {
        return $this->result;
    }
}
