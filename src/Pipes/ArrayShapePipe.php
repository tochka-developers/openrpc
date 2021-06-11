<?php

namespace Tochka\OpenRpc\Pipes;

use Doctrine\Common\Annotations\Reader;
use phpDocumentor\Reflection\DocBlockFactory;
use Tochka\OpenRpc\Annotations\ApiArrayShape;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\DTO\SchemaReference;
use Tochka\OpenRpc\ExpectedPipeObject;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\VarType;

class ArrayShapePipe implements PropertyPipeInterface
{
    private Reader $annotationReader;
    private DocBlockFactory $docBlockFactory;
    
    public function __construct(Reader $annotationReader, DocBlockFactory $docBlockFactory)
    {
        $this->annotationReader = $annotationReader;
        $this->docBlockFactory = $docBlockFactory;
    }
    
    /**
     * @throws \ReflectionException
     */
    public function handle(ExpectedPipeObject $expected, callable $next): ExpectedPipeObject
    {
        if (!$expected->schema instanceof Schema) {
            return $next($expected);
        }
        
        $annotation = null;
        
        if ($expected->varType->builtIn) {
            /** @var ApiArrayShape $annotation */
            if ($expected->property !== null) {
                $annotation = $this->annotationReader->getPropertyAnnotation($expected->property, ApiArrayShape::class);
            } elseif ($expected->method !== null) {
                $annotation = $this->annotationReader->getMethodAnnotation($expected->method, ApiArrayShape::class);
            }
            
            if ($annotation !== null) {
                $expected->schema->type = VarType::TYPE_OBJECT;
                $expected->schema->properties = $this->getPropertiesFromShape($expected, $annotation->shape);
                $expected->schema->required = array_keys($annotation->shape);
                return $expected;
            }
        } elseif ($expected->varType->className !== null) {
            $reflectionClass = new \ReflectionClass($expected->varType->className);
            /** @var ApiArrayShape $annotation */
            $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, ApiArrayShape::class);
            
            if ($annotation !== null) {
                if (!$expected->schemasDictionary->hasSchema($expected->varType->className)) {
                    $schema = new Schema();
                    set_title_and_description_from_class(
                        $schema,
                        $expected->varType->className,
                        $this->docBlockFactory
                    );
                    $schema->type = VarType::TYPE_OBJECT;
                    $schema->properties = $this->getPropertiesFromShape($annotation->shape);
                    $schema->required = array_keys($annotation->shape);
                    
                    $expected->schemasDictionary->addSchema($schema, $expected->varType->className);
                }
                
                $expected->schema = new SchemaReference($expected->varType->className, $expected->schemasDictionary);
                
                return $expected;
            }
        }
        
        return $next($expected);
    }
    
    private function getPropertiesFromShape(array $shape): array
    {
        $properties = [];
        
        foreach ($shape as $field => $type) {
            $schema = new Schema();
            $varType = new VarType($type);
            $schema->type = $varType->isArray ? VarType::TYPE_ARRAY : $varType->getSchemaType();
            
            $childExpected = MethodDescription::sendThroughPipes(null, $schema, $varType);
            
            $properties[$field] = $childExpected->schema;
        }
        
        return $properties;
    }
}
