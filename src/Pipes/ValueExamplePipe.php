<?php

namespace Tochka\OpenRpc\Pipes;

use Tochka\JsonRpc\Annotations\ApiValueExample;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\Support\ClassContext;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;
use Tochka\OpenRpc\Support\MethodContext;

class ValueExamplePipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        $docBlock = null;
        
        if ($result->context instanceof MethodContext && $result->isResult) {
            $docBlock = JsonRpcDocBlockFactory::makeForMethod(
                $result->context->getClassName(),
                $result->context->getMethodName()
            );
        }
        
        if ($result->context instanceof ClassContext && !empty($result->parameter->name)) {
            $docBlock = JsonRpcDocBlockFactory::makeForProperty(
                $result->context->getClassName(),
                $result->parameter->name
            );
        }
        
        if ($docBlock === null) {
            return $result;
        }
        
        $annotation = $docBlock->firstAnnotation(ApiValueExample::class);
        
        if (!empty($annotation)) {
            $result->schema->getSchema()->examples = $annotation->examples;
        }
        
        return $result;
    }
}
