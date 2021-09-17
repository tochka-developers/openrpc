<?php

namespace Tochka\OpenRpc;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\Deprecated;
use phpDocumentor\Reflection\DocBlock;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Facades\JsonRpcParamsResolver;
use Tochka\JsonRpc\Facades\JsonRpcRouteAggregator;
use Tochka\JsonRpc\Route\JsonRpcRoute;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Support\JsonRpcDocBlock;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\OpenRpc\Contracts\ContentDescriptorReferenceInterface;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\Contracts\TagReferenceInterface;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\DTO\ContentDescriptor;
use Tochka\OpenRpc\DTO\Example;
use Tochka\OpenRpc\DTO\Method;
use Tochka\OpenRpc\DTO\References\ContentDescriptorReference;
use Tochka\OpenRpc\DTO\References\SchemaReference;
use Tochka\OpenRpc\DTO\References\TagReference;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\DTO\Tag;
use Tochka\OpenRpc\Support\AliasedDictionary;
use Tochka\OpenRpc\Support\ClassContext;
use Tochka\OpenRpc\Support\Context;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;
use Tochka\OpenRpc\Support\MethodContext;
use Tochka\OpenRpc\Support\StrSupport;

class MethodDescriptionGenerator
{
    private Pipeline $pipeline;
    /** @var AliasedDictionary<ContentDescriptor> */
    private AliasedDictionary $contentDescriptorsDictionary;
    /** @var AliasedDictionary<Schema> */
    private AliasedDictionary $schemasDictionary;
    /** @var AliasedDictionary<Example> */
    private AliasedDictionary $examplesDictionary;
    /** @var AliasedDictionary<Tag> */
    private AliasedDictionary $tagDictionary;
    /** @var array<SchemaHandlerPipeInterface> */
    private array $pipes = [];
    
    /**
     * @throws BindingResolutionException
     */
    public function __construct(Container $container)
    {
        $this->schemasDictionary = new AliasedDictionary();
        $this->examplesDictionary = new AliasedDictionary();
        $this->tagDictionary = new AliasedDictionary();
        $this->contentDescriptorsDictionary = new AliasedDictionary();
        $this->pipeline = $container->make(Pipeline::class);
    }
    
    public function addPipe(SchemaHandlerPipeInterface $pipe): void
    {
        $this->pipes[] = $pipe;
    }
    
    public function generate(array $jsonRpcConfig, array $servers): array
    {
        $routes = JsonRpcRouteAggregator::getRoutes();
        $serverConfigs = [];
        
        foreach ($jsonRpcConfig as $serverName => $serverConfig) {
            $serverConfigs[$serverName] = new ServerConfig($serverConfig);
        }
        
        /** @var array<Method> $methods */
        $methods = [];
        foreach ($routes as $route) {
            if (!isset($serverConfigs[$route->serverName])) {
                throw new \RuntimeException(
                    sprintf(
                        'Error while map route with server configuration. Route [%s], server name [%s]',
                        $route->getRouteName(),
                        $route->serverName
                    )
                );
            }
            $methodKey = $route->controllerClass . '@' . $route->controllerMethod;
            $controllerSuffix = $serverConfigs[$route->serverName]->controllerSuffix;
            
            if (array_key_exists($methodKey, $methods)) {
                $methods[$methodKey]->servers[] = $servers[$route->serverName];
                continue;
            }
            $docBlock = JsonRpcDocBlockFactory::makeForMethod($route->controllerClass, $route->controllerMethod);
            
            $method = new Method($route->jsonRpcMethodName, [], new ContentDescriptor('result', new Schema()));
            if ($docBlock !== null) {
                $method->summary = $docBlock->getSummary();
                $method->description = StrSupport::resolveRef($docBlock->getDescription());
                $method->deprecated = $this->openRpcDocBlockIsDeprecated($docBlock);
            }
            
            $method->servers[] = $servers[$route->serverName];
            $method->tags[] = $this->getTagForGroup(
                $route->controllerClass,
                $controllerSuffix,
                $route->group,
                $route->action
            );
            
            $method->params = $this->getParametersFromRoute($route);
            $method->result = $this->getResultFromRoute($route);
            
            $methods[$methodKey] = $method;
        }
        
        return array_values($methods);
    }
    
    /**
     * @param JsonRpcRoute $route
     * @return array<ContentDescriptorReferenceInterface>
     */
    private function getParametersFromRoute(JsonRpcRoute $route): array
    {
        $contentDescriptors = [];
        foreach ($route->parameters as $parameter) {
            if ($parameter->castFromDI) {
                continue;
            }
            
            if ($parameter->castFullRequest) {
                return $this->getContentDescriptorsForRequest($parameter);
            }
            
            $contentDescriptors[] = $this->getContentDescriptor(
                $parameter,
                new MethodContext($route->controllerClass, $route->controllerMethod)
            );
        }
        
        return $contentDescriptors;
    }
    
    private function getResultFromRoute(JsonRpcRoute $route): ContentDescriptorReferenceInterface
    {
        return $this->getContentDescriptor(
            $route->result,
            new MethodContext($route->controllerClass, $route->controllerMethod),
            true
        );
    }
    
    /**
     * @param Parameter $parameter
     * @return array<ContentDescriptorReferenceInterface>
     */
    private function getContentDescriptorsForRequest(Parameter $parameter): array
    {
        $contentDescriptors = [];
        
        $parameterObject = JsonRpcParamsResolver::getParameterObject($parameter->className);
        if ($parameterObject === null) {
            return $contentDescriptors;
        }
        
        foreach ($parameterObject->properties as $property) {
            $contentDescriptorName = $parameter->className . '::' . $property->name;
            $contentDescriptors[] = $this->contentDescriptorsDictionary->getReference(
                $contentDescriptorName,
                ContentDescriptorReference::class,
                function () use ($property, $parameter) {
                    return $this->getContentDescriptor($property, new ClassContext($parameter->className));
                }
            );
        }
        
        return $contentDescriptors;
    }
    
    private function getContentDescriptor(
        Parameter $parameter,
        Context $context,
        bool $isResult = false
    ): ?ContentDescriptorReferenceInterface {
        $contentDescriptor = new ContentDescriptor(
            $parameter->name,
            $this->getSchemaWithPipes($parameter, $context, $isResult)
        );
        $contentDescriptor->summary = $parameter->description;
        $contentDescriptor->description = $parameter->description;
        $contentDescriptor->required = $parameter->required;
        
        if ($context instanceof ClassContext) {
            $propertyDocBlock = JsonRpcDocBlockFactory::makeForProperty($context->getClassName(), $parameter->name);
            if ($propertyDocBlock !== null) {
                $contentDescriptor->deprecated = $this->openRpcDocBlockIsDeprecated($propertyDocBlock);
                
                $summary = $propertyDocBlock->getSummary();
                $description = StrSupport::resolveRef($propertyDocBlock->getDescription());
                if (!empty($summary)) {
                    $contentDescriptor->summary = $summary;
                }
                if (!empty($description)) {
                    $contentDescriptor->description = $description;
                }
            }
        }
        
        return $contentDescriptor;
    }
    
    public function getSchemaWithPipes(
        Parameter $parameter,
        Context $context,
        bool $isResult = false
    ): ?SchemaReferenceInterface {
        $schema = new Schema();
        $schema->type = $parameter->type->toJsonType();
        $schema->title = $parameter->description;
        $schema->description = $parameter->description;
        
        if (!$parameter->required) {
            $schema->default = $parameter->defaultValue;
        }
        
        if ($context instanceof ClassContext) {
            $propertyDocBlock = JsonRpcDocBlockFactory::makeForProperty($context->getClassName(), $parameter->name);
            
            if ($propertyDocBlock !== null) {
                $summary = $propertyDocBlock->getSummary();
                $description = StrSupport::resolveRef($propertyDocBlock->getDescription());
                if (!empty($summary)) {
                    $schema->title = $summary;
                }
                if (!empty($description)) {
                    $schema->description = $description;
                }
            }
        }
        
        $expected = new ExpectedSchemaPipeObject($schema, $this->schemasDictionary, $parameter, $context, $isResult);
        
        /** @var ExpectedSchemaPipeObject $result */
        $result = $this->pipeline->send($expected)
            ->through($this->pipes)
            ->then(
                function (ExpectedSchemaPipeObject $expected) {
                    $expected->schema = $this->getSchema(
                        $expected->schema,
                        $expected->parameter,
                        $expected->context
                    );
                    return $expected;
                }
            );
        
        return $result->schema;
    }
    
    private function getSchema(
        SchemaReferenceInterface $schemaReference,
        Parameter $parameter,
        Context $context
    ): SchemaReferenceInterface {
        $result = $schemaReference;
        
        if ($parameter->className !== null && $parameter->type->is(ParameterTypeEnum::TYPE_OBJECT())) {
            /** @var SchemaReference $result */
            $result = $this->schemasDictionary->getReference(
                StrSupport::fullyQualifiedClassName($parameter->className),
                SchemaReference::class,
                function () use ($schemaReference, $parameter) {
                    return $this->getSchemaForClass($schemaReference->getSchema(), $parameter);
                }
            );
        }
        
        $schema = $result->getSchema();
        
        if (
            $parameter->parametersInArray !== null
            && $parameter->type->is(ParameterTypeEnum::TYPE_ARRAY())
        ) {
            $schema->items = $this->getSchemaWithPipes($parameter->parametersInArray, $context);
        }
        
        return $result;
    }
    
    private function getSchemaForClass(Schema $schema, Parameter $parameter): Schema
    {
        $classDocBlock = JsonRpcDocBlockFactory::makeForClass($parameter->className);
        
        if ($classDocBlock !== null) {
            $schema->title = $classDocBlock->getSummary();
            $schema->description = StrSupport::resolveRef($classDocBlock->getDescription());
        }
        
        $parameterObject = JsonRpcParamsResolver::getParameterObject($parameter->className);
        if ($parameterObject === null || $parameterObject->properties === null) {
            return $schema;
        }
        
        foreach ($parameterObject->properties as $property) {
            if ($property->required) {
                $schema->required[] = $property->name;
            }
            
            $schema->properties[$property->name] = $this->getSchemaWithPipes(
                $property,
                new ClassContext($parameter->className)
            );
        }
        
        return $schema;
    }
    
    /**
     * Возвращает ссылку на тег для группы методов
     */
    private function getTagForGroup(
        string $controller,
        string $controllerSuffix,
        ?string $group = null,
        ?string $action = null
    ): TagReferenceInterface {
        $groupName = implode(
            '-',
            array_filter([
                             $group,
                             $action,
                             $this->getShortNameForController($controller, $controllerSuffix)
                         ])
        );
        
        return $this->tagDictionary->getReference(
            $groupName,
            TagReference::class,
            function () use ($groupName, $controller) {
                $tag = new Tag($groupName);
                
                $docBlock = JsonRpcDocBlockFactory::makeForClass($controller);
                if ($docBlock !== null) {
                    $tag->summary = $docBlock->getSummary();
                    $tag->description = StrSupport::resolveRef($docBlock->getDescription());
                }
                
                return $tag;
            }
        );
    }
    
    /**
     * Возвращает список всех подготовленных компонент
     *
     * @return Components
     */
    public function getComponents(): Components
    {
        $components = new Components();
        
        $components->tags = $this->tagDictionary->getAliasedItems();
        $components->schemas = $this->schemasDictionary->getAliasedItems();
        $components->examples = $this->examplesDictionary->getAliasedItems();
        $components->contentDescriptors = $this->contentDescriptorsDictionary->getAliasedItems();
        
        return $components;
    }
    
    private function getShortNameForController(string $name, string $controllerSuffix): string
    {
        return Str::camel(Str::replaceLast($controllerSuffix, '', class_basename($name)));
    }
    
    private function openRpcDocBlockIsDeprecated(JsonRpcDocBlock $docBlock): bool
    {
        return $docBlock->hasTag(DocBlock\Tags\Deprecated::class) || $docBlock->hasAnnotation(Deprecated::class);
    }
}
