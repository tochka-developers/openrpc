<?php

namespace Tochka\OpenRpc\Pipes;

use Doctrine\Common\Annotations\Reader;
use Tochka\OpenRpc\Annotations\ApiExpectedValues;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\ExpectedPipeObject;

class ExpectedValuesPipe implements PropertyPipeInterface
{
    private Reader $annotationReader;
    
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }
    
    public function handle(ExpectedPipeObject $expected, callable $next): ExpectedPipeObject
    {
        if (
            $expected->schema instanceof Schema
            && $expected->varType !== null
            && $expected->varType->builtIn
        ) {
            /** @var ApiExpectedValues $annotation */
            if ($expected->property !== null) {
                $annotation = $this->annotationReader->getPropertyAnnotation(
                    $expected->property,
                    ApiExpectedValues::class
                );
            } elseif ($expected->method !== null) {
                $annotation = $this->annotationReader->getMethodAnnotation($expected->method, ApiExpectedValues::class);
            } else {
                $annotation = null;
            }
            
            if ($annotation !== null) {
                $expected->schema->enum = $annotation->values;
            }
        }
        
        return $next($expected);
    }
}
