<?php

namespace Tochka\OpenRpc\Pipes;

use Tochka\JsonRpc\Annotations\ApiExpectedValues;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\Support\ClassContext;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;

class ExpectedValuesPipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        if (
            empty($result->parameter->name)
            || !$result->context instanceof ClassContext
        ) {
            return $result;
        }
        
        $docBlock = JsonRpcDocBlockFactory::makeForProperty($result->context->getClassName(), $result->parameter->name);
        if ($docBlock === null) {
            return $result;
        }
        
        $annotation = $docBlock->firstAnnotation(ApiExpectedValues::class);
        
        if (!empty($annotation)) {
            $result->schema->getSchema()->enum = $annotation->values;
        }
        
        return $result;
    }
}
