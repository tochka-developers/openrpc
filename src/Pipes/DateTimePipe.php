<?php

namespace Tochka\OpenRpc\Pipes;

use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\OpenRpc\Contracts\SchemaHandlerPipeInterface;
use Tochka\OpenRpc\Support\ExpectedSchemaPipeObject;

class DateTimePipe implements SchemaHandlerPipeInterface
{
    public function handle(ExpectedSchemaPipeObject $expected, callable $next): ExpectedSchemaPipeObject
    {
        /** @var ExpectedSchemaPipeObject $result */
        $result = $next($expected);
        
        if (!is_subclass_of($result->parameter->className, \DateTime::class)) {
            return $result;
        }
        
        $schema = $result->schema->getSchema();
        
        $schema->type = ParameterTypeEnum::TYPE_STRING()->toJsonType();
        $schema->required = [];
        $schema->properties = [];
        $schema->format = 'datetime';
        $schema->title = 'DateTime';
        $schema->description = null;
        
        return $result;
    }
}
