<?php

namespace Tochka\OpenRpc;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\ContextFactory;
use Spiral\Attributes\ReaderInterface;
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiIgnoreMethod;
use Tochka\JsonRpc\Contracts\JsonRpcRequestInterface;
use Tochka\JsonRpc\Support\JsonRpcHandleResolver;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\DTO\ContentDescriptor;
use Tochka\OpenRpc\DTO\Method;
use Tochka\OpenRpc\DTO\Reference;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\DTO\SchemaReference;
use Tochka\OpenRpc\DTO\Tag;

class MethodDescriptionGenerator
{
    private ReaderInterface $annotationReader;
    private DocBlockFactory $docBlockFactory;
    private ControllerFinder $finder;
    
    private array $tags = [];
    private SchemasDictionary $schemasDictionary;
    /** @var array<PropertyPipeInterface> */
    private array $pipes = [];
    
    public function __construct(
        ReaderInterface $annotationReader,
        DocBlockFactory $docBlockFactory,
        ControllerFinder $finder
    ) {
        $this->annotationReader = $annotationReader;
        $this->docBlockFactory = $docBlockFactory;
        $this->finder = $finder;
        $this->schemasDictionary = new SchemasDictionary();
    }
    
    public function addPipe(PropertyPipeInterface $pipe): void
    {
        $this->pipes[] = $pipe;
    }
    
    /**
     * @throws \ReflectionException
     * @throws \JsonException
     * @throws BindingResolutionException
     */
    public function generate(array $jsonRpcConfig, array $servers): array
    {
        // берем каждый сервер, из него получаем namespace, и далее ищем в этом namespace все подходящие классы
        /** @var array<Method> $methods */
        $methods = [];
        foreach ($jsonRpcConfig as $serverName => $server) {
            $config = new ServerConfig($server);
            $controllers = $this->finder->find($config->namespace, $config->controllerSuffix);
            
            foreach ($controllers as $controller) {
                $reflectionClass = new \ReflectionClass($controller);
                
                if ($this->ignoreThis($reflectionClass)) {
                    continue;
                }
                $ignoredMethods = $this->getIgnoredMethodsFromController($reflectionClass);
                
                $reflectionMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
                
                foreach ($reflectionMethods as $reflectionMethod) {
                    if ($reflectionMethod->getDeclaringClass()->getName() !== $controller) {
                        continue;
                    }
                    $methodKey = $controller . '@' . $reflectionMethod->getName();
                    
                    if (array_key_exists($methodKey, $methods)) {
                        $methods[$methodKey]->servers[] = $servers[$serverName];
                        continue;
                    }
                    
                    if ($this->ignoreThis($reflectionMethod) || in_array(
                            $reflectionMethod->getName(),
                            $ignoredMethods,
                            true
                        )) {
                        continue;
                    }
                    
                    $methodName = $this->getShortNameForController(
                            $controller,
                            $config->controllerSuffix
                        ) . $config->methodDelimiter . $reflectionMethod->getName();
                    
                    
                    $method = new Method($methodName, [], new ContentDescriptor('result', new Schema()));
                    $method->servers[] = $servers[$serverName];
                    $method->tags = [$this->getTagForGroup($controller, $config->controllerSuffix)];
                    
                    $docComment = $reflectionMethod->getDocComment();
                    $phpDocContext = (new ContextFactory())->createFromReflector($reflectionMethod);
                    $methodDocBlock = $docComment !== false ? $this->docBlockFactory->create(
                        $docComment,
                        $phpDocContext
                    ) : null;
                    
                    if ($methodDocBlock !== null) {
                        $method->summary = $methodDocBlock->getSummary();
                        $description = $methodDocBlock->getDescription()->getBodyTemplate();
                        if (!empty($description)) {
                            set_field_if_not_empty($description, $method, 'description');
                        } else {
                            $method->description = $methodDocBlock->getSummary();
                        }
                        
                        if ($methodDocBlock->hasTag('deprecated')) {
                            $method->deprecated = true;
                        }
                    }
                    
                    $reflectionParameters = $reflectionMethod->getParameters();
                    if ($config->paramsResolver === JsonRpcHandleResolver::PARAMS_RESOLVER_DTO) {
                        $mainDTOClass = null;
                        foreach ($reflectionParameters as $reflectionParameter) {
                            $reflectionType = $reflectionParameter->getType();
                            if (
                                $reflectionType instanceof \ReflectionNamedType
                                && \in_array(
                                    JsonRpcRequestInterface::class,
                                    class_implements($reflectionType->getName()),
                                    true
                                )
                            ) {
                                $mainDTOClass = $reflectionType->getName();
                                break;
                            }
                        }
                        if ($mainDTOClass !== null) {
                            $method->params = $this->getParametersFromDTOForMethod($mainDTOClass);
                        }
                    }
                    $method->result = $this->getReturnValuesFromMethod($reflectionMethod, $methodDocBlock);
                    $methods[$methodKey] = $method;
                }
            }
        }
        
        return array_values($methods);
    }
    
    /**
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    private function getParametersFromDTOForMethod(string $className): array
    {
        $contentDescriptors = [];
        
        $reflectionClass = new \ReflectionClass($className);
        $reflectionProperties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        $defaultProperties = $reflectionClass->getDefaultProperties();
        
        foreach ($reflectionProperties as $reflectionProperty) {
            $docComment = $reflectionProperty->getDocComment();
            $phpDocContext = (new ContextFactory())->createFromReflector($reflectionProperty);
            $docBlock = $docComment !== false ? $this->docBlockFactory->create($docComment, $phpDocContext) : null;
            
            $contentDescriptors[] = $this->getContentDescriptorForProperty(
                $reflectionProperty,
                $defaultProperties,
                $docBlock
            );
        }
        
        return $contentDescriptors;
    }
    
    /**
     * @throws BindingResolutionException
     */
    private function getContentDescriptorForProperty(
        \ReflectionProperty $reflectionProperty,
        array $defaultProperties,
        ?DocBlock $docBlock
    ): ContentDescriptor {
        $contentDescriptor = new ContentDescriptor(
            $reflectionProperty->getName(),
            $this->getSchemaForProperty($reflectionProperty, $docBlock, $defaultProperties)
        );
        
        if (!empty($contentDescriptor->schema->getSchema()->title)) {
            $contentDescriptor->summary = $contentDescriptor->schema->getSchema()->title;
        }
        
        if (!empty($contentDescriptor->schema->getSchema()->description)) {
            $contentDescriptor->description = $contentDescriptor->schema->getSchema()->description;
        }
        
        if ($docBlock !== null && $docBlock->hasTag('deprecated')) {
            $contentDescriptor->deprecated = true;
        }
        
        $contentDescriptor->required = !array_key_exists($reflectionProperty->getName(), $defaultProperties);
        
        return $contentDescriptor;
    }
    
    /**
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getParametersFromDTO(Schema $schema, string $className): SchemaReferenceInterface
    {
        $reflectionClass = new \ReflectionClass($className);
        $reflectionProperties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        $defaultProperties = $reflectionClass->getDefaultProperties();
        
        $requiredProperties = [];
        $schema->properties = [];
        
        foreach ($reflectionProperties as $reflectionProperty) {
            $docComment = $reflectionProperty->getDocComment();
            $phpDocContext = (new ContextFactory())->createFromReflector($reflectionProperty);
            $docBlock = $docComment !== false ? $this->docBlockFactory->create($docComment, $phpDocContext) : null;
            $schema->properties[$reflectionProperty->getName()] = $this->getSchemaForProperty(
                $reflectionProperty,
                $docBlock,
                $defaultProperties
            );
            
            if (!array_key_exists($reflectionProperty->getName(), $defaultProperties)) {
                $requiredProperties[] = $reflectionProperty->getName();
            }
        }
        
        $propertiesTags = $this->getPropertyTagsFromDTO($reflectionClass);
        
        foreach ($propertiesTags as $tag) {
            if (array_key_exists($tag->getVariableName(), $schema->properties)) {
                $propertySchema = $schema->properties[$tag->getVariableName()];
            } else {
                $propertySchema = new Schema();
            }
            
            $schema->properties[$tag->getVariableName()] = $this->getSchemaForPropertyTag($propertySchema, $tag);
            if (!in_array($tag->getVariableName(), $requiredProperties, true)) {
                $requiredProperties[] = $tag->getVariableName();
            }
        }
        
        set_title_and_description_from_class($schema, $className, $this->docBlockFactory);
        $schema->required = $requiredProperties;
        
        return $schema;
    }
    
    /**
     * @param \ReflectionClass $reflectionClass
     * @return array<Property|PropertyRead|PropertyWrite>
     */
    public function getPropertyTagsFromDTO(\ReflectionClass $reflectionClass): array
    {
        $docComment = $reflectionClass->getDocComment();
        $phpDocContext = (new ContextFactory())->createFromReflector($reflectionClass);
        $phpDoc = $docComment !== false ? $this->docBlockFactory->create($docComment, $phpDocContext) : null;
        if ($phpDoc !== null) {
            return array_filter(
                $phpDoc->getTags(),
                fn($tag) => $tag instanceof Property || $tag instanceof PropertyRead || $tag instanceof PropertyWrite
            );
        }
        
        return [];
    }
    
    /**
     * @throws BindingResolutionException
     */
    private function getSchemaForProperty(
        \ReflectionProperty $reflectionProperty,
        ?DocBlock $docBlock,
        array $defaultProperties
    ): SchemaReferenceInterface {
        $schema = new Schema();
        $varType = $this->getTypeForReflection($reflectionProperty->getType(), $docBlock, 'var');
        $schema->type = $varType->isArray ? VarType::TYPE_ARRAY : $varType->getSchemaType();
        if (array_key_exists($reflectionProperty->getName(), $defaultProperties)) {
            $schema->default = $defaultProperties[$reflectionProperty->getName()];
        }
        
        if ($docBlock !== null) {
            if (!empty($docBlock->getSummary())) {
                $schema->title = $docBlock->getSummary();
            }
            
            $description = $docBlock->getDescription()->getBodyTemplate();
            if (!empty($description)) {
                set_field_if_not_empty($description, $schema, 'description');
            } elseif (empty($schema->description) && !empty($docBlock->getSummary())) {
                $schema->description = $docBlock->getSummary();
            }
        }
        
        $expected = $this->sendThroughPipes($reflectionProperty, $schema, $varType, $docBlock);
        
        return $expected->schema;
    }
    
    /**
     * @param Schema $schema
     * @param Property|PropertyRead|PropertyWrite $tag
     * @return SchemaReferenceInterface
     * @throws BindingResolutionException
     */
    private function getSchemaForPropertyTag(Schema $schema, DocBlock\Tag $tag): SchemaReferenceInterface
    {
        $varType = $this->getVarTypeFromPhpDocType($tag->getType());
        $schema->type = $varType->isArray ? VarType::TYPE_ARRAY : $varType->getSchemaType();
        $description = $tag->getDescription();
        if ($description !== null) {
            set_field_if_not_empty($description->getBodyTemplate(), $schema, 'title');
            set_field_if_not_empty($description->getBodyTemplate(), $schema, 'description');
        }
        
        return $this->sendThroughPipes(null, $schema, $varType)->schema;
    }
    
    /**
     * @throws BindingResolutionException
     */
    public function sendThroughPipes(
        ?\Reflector $reflector,
        SchemaReferenceInterface $schema,
        VarType $varType,
        ?DocBlock $docBlock = null
    ): ExpectedPipeObject {
        /** @var Pipeline $pipeline */
        $pipeline = Container::getInstance()->make(Pipeline::class);
        $expected = new ExpectedPipeObject($reflector, $schema, $varType, $this->schemasDictionary, $docBlock);
        
        return $pipeline->send($expected)
            ->through($this->pipes)
            ->then(
                function (ExpectedPipeObject $expected) {
                    return $this->getSchemaForObjectOrArray($expected);
                }
            );
    }
    
    /**
     * @throws \ReflectionException
     */
    private function getSchemaForObjectOrArray(ExpectedPipeObject $expected): ExpectedPipeObject
    {
        if (!$expected->schema instanceof Schema || $expected->varType === null) {
            return $expected;
        }
        
        if (!$expected->varType->builtIn && !$expected->varType->isArray && $expected->varType->className !== null) {
            if (!$expected->schemasDictionary->hasSchema($expected->varType->className)) {
                $expected->schemasDictionary->addSchema(
                    $this->getParametersFromDTO($expected->schema, $expected->varType->className),
                    $expected->varType->className
                );
            }
            
            $expected->schema = new SchemaReference($expected->varType->className, $expected->schemasDictionary);
        }
        
        if ($expected->varType->isArray) {
            if (!$expected->varType->builtIn && $expected->varType->className !== null) {
                if (!$this->schemasDictionary->hasSchema($expected->varType->className)) {
                    $internalSchema = new Schema();
                    $internalSchema->type = VarType::TYPE_OBJECT;
                    $this->schemasDictionary->addSchema(
                        $this->getParametersFromDTO($internalSchema, $expected->varType->className),
                        $expected->varType->className
                    );
                }
                
                $expected->schema->items = new SchemaReference($expected->varType->className, $this->schemasDictionary);
            } else {
                $internalSchema = new Schema();
                $internalSchema->type = $expected->varType->getSchemaType();
                $expected->schema->items = $internalSchema;
            }
            
            $expected->schema->type = VarType::TYPE_ARRAY;
        }
        
        return $expected;
    }
    
    /**
     * @throws BindingResolutionException
     */
    private function getReturnValuesFromMethod(
        \ReflectionMethod $reflectionMethod,
        ?DocBlock $docBlock
    ): ContentDescriptor {
        $contentDescriptor = new ContentDescriptor('result', $this->getSchemaForResult($reflectionMethod, $docBlock));
        $contentDescriptor->required = true;
        
        if (!empty($contentDescriptor->schema->getSchema()->title)) {
            $contentDescriptor->summary = $contentDescriptor->schema->getSchema()->title;
        }
        
        if (!empty($contentDescriptor->schema->getSchema()->description)) {
            $contentDescriptor->description = $contentDescriptor->schema->getSchema()->description;
        }
        
        if ($docBlock !== null) {
            /** @var DocBlock\Tags\Return_[] $returnTags */
            $returnTags = $docBlock->getTagsByName('return');
            if (!empty($returnTags)) {
                $contentDescriptor->summary = $returnTags[0]->getDescription();
                if (empty($contentDescriptor->description)) {
                    $contentDescriptor->description = $returnTags[0]->getDescription();
                }
            }
        }
        
        return $contentDescriptor;
    }
    
    /**
     * @throws BindingResolutionException
     */
    private function getSchemaForResult(
        \ReflectionMethod $reflectionMethod,
        ?DocBlock $docBlock
    ): SchemaReferenceInterface {
        $schema = new Schema();
        
        $varType = $this->getTypeForReflection($reflectionMethod->getReturnType(), $docBlock, 'return');
        $schema->type = $varType->isArray ? VarType::TYPE_ARRAY : $varType->getSchemaType();
        
        $expected = $this->sendThroughPipes($reflectionMethod, $schema, $varType, $docBlock);
        
        return $expected->schema;
    }
    
    /**
     * Возвращает тип переданного параметра
     * Извлекает его из указанного типа, либо из PHPDoc
     *
     * @param \ReflectionType|null $reflectionType
     * @param DocBlock|null $docBlock
     * @param string $tagName
     * @return VarType
     */
    private function getTypeForReflection(
        ?\ReflectionType $reflectionType,
        ?DocBlock $docBlock,
        string $tagName
    ): VarType {
        $varType = null;
        
        if ($reflectionType instanceof \ReflectionNamedType) {
            $varType = new VarType($reflectionType->getName());
        } elseif ($reflectionType instanceof \ReflectionUnionType) {
            $types = $reflectionType->getTypes();
            $varType = new VarType($types[0]->getName());
        }
        
        if ($docBlock !== null && ($varType === null || $varType->isArray)) {
            /** @var array<DocBlock\Tags\TagWithType> $varTags */
            $tagsWithType = $docBlock->getTagsByName($tagName);
            if (!empty($tagsWithType)) {
                $varType = $this->getVarTypeFromPhpDocType($tagsWithType[0]->getType(), $varType);
            }
        }
        
        if ($varType === null) {
            $varType = new VarType(VarType::TYPE_MIXED);
        }
        
        return $varType;
    }
    
    public function getVarTypeFromPhpDocType(Type $type, ?VarType $varType = null): VarType
    {
        if ($type instanceof Compound) {
            $type = $type->get(0);
        }
        
        $isArray = $varType && $varType->isArray;
        if ($type instanceof Array_) {
            $varType = new VarType((string)$type->getValueType(), true);
        } else {
            $varType = new VarType((string)$type, $isArray);
        }
        
        return $varType;
    }
    
    /**
     * Возвращает список методов, которые не нужно описывать в документации
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getIgnoredMethodsFromController(\ReflectionClass $reflectionClass): array
    {
        $ignoredMethods = [];
        $classAnnotations = $this->annotationReader->getClassMetadata($reflectionClass);
        
        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof ApiIgnoreMethod && $classAnnotation->name !== null) {
                $ignoredMethods[] = $classAnnotation->name;
            }
        }
        
        return $ignoredMethods;
    }
    
    /**
     * Возвращает метку, нужно ли игнорировать текущий контроллер или метод в нем при описании документации
     *
     * @param \Reflector $reflection
     *
     * @return bool
     */
    private function ignoreThis(\Reflector $reflection): bool
    {
        if ($reflection instanceof \ReflectionClass) {
            $ignoreAnnotation = $this->annotationReader->getClassMetadata($reflection, ApiIgnore::class);
            if (!empty($ignoreAnnotation)) {
                return true;
            }
        }
        
        if ($reflection instanceof \ReflectionMethod) {
            $ignoreAnnotation = $this->annotationReader->getFunctionMetadata($reflection, ApiIgnore::class);
            
            if (!empty($ignoreAnnotation)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Возвращает ссылку на тег для группы методов
     *
     * @param string $controller
     * @param string $controllerSuffix
     *
     * @return Reference
     */
    private function getTagForGroup(string $controller, string $controllerSuffix): Reference
    {
        $groupName = $this->getShortNameForController($controller, $controllerSuffix);
        if (!array_key_exists($groupName, $this->tags)) {
            $this->tags[$groupName] = new Tag($groupName);
        }
        
        return new Reference('#' . '/components/tags/' . $groupName);
    }
    
    /**
     * Возвращает список всех подготовленных компонент
     *
     * @return Components
     */
    public function getComponents(): Components
    {
        $components = new Components();
        if (!empty($this->tags)) {
            $components->tags = $this->tags;
        }
        $schemas = $this->schemasDictionary->getAliasedSchemas();
        if (!empty($schemas)) {
            $components->schemas = $schemas;
        }
        
        return $components;
    }
    
    private function getShortNameForController(string $name, string $controllerSuffix): string
    {
        return Str::camel(Str::replaceLast($controllerSuffix, '', class_basename($name)));
    }
}
