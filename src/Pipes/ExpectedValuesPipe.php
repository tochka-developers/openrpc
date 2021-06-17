<?php

namespace Tochka\OpenRpc\Pipes;

use Spiral\Attributes\ReaderInterface;
use Tochka\JsonRpc\Annotations\ApiExpectedValues;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\ExpectedPipeObject;

class ExpectedValuesPipe implements PropertyPipeInterface
{
    private ReaderInterface $annotationReader;
    
    public function __construct(ReaderInterface $annotationReader)
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
                $annotation = $this->annotationReader->firstPropertyMetadata(
                    $expected->property,
                    ApiExpectedValues::class
                );
            } elseif ($expected->method !== null) {
                $annotation = $this->annotationReader->firstFunctionMetadata($expected->method, ApiExpectedValues::class);
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
