<?php

namespace Tochka\OpenRpc\Pipes;

use Spiral\Attributes\ReaderInterface;
use Tochka\JsonRpc\Annotations\ApiValueExample;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\ExpectedPipeObject;

class ValueExamplePipe implements PropertyPipeInterface
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
            /** @var ApiValueExample $annotation */
            if ($expected->property !== null) {
                $annotation = $this->annotationReader->getPropertyMetadata(
                    $expected->property,
                    ApiValueExample::class
                );
            } elseif ($expected->method !== null) {
                $annotation = $this->annotationReader->getFunctionMetadata($expected->method, ApiValueExample::class);
            } else {
                $annotation = null;
            }
            
            if ($annotation !== null) {
                $expected->schema->examples = $annotation->examples;
            }
        }
        
        return $next($expected);
    }
}
