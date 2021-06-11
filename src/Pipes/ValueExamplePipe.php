<?php

namespace Tochka\OpenRpc\Pipes;

use Doctrine\Common\Annotations\Reader;
use Tochka\OpenRpc\Annotations\ApiValueExample;
use Tochka\OpenRpc\Contracts\PropertyPipeInterface;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\ExpectedPipeObject;

class ValueExamplePipe implements PropertyPipeInterface
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
            /** @var ApiValueExample $annotation */
            if ($expected->property !== null) {
                $annotation = $this->annotationReader->getPropertyAnnotation(
                    $expected->property,
                    ApiValueExample::class
                );
            } elseif ($expected->method !== null) {
                $annotation = $this->annotationReader->getMethodAnnotation($expected->method, ApiValueExample::class);
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
