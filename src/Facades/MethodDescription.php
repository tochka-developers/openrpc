<?php

namespace Tochka\OpenRpc\Facades;

use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\MethodDescriptionGenerator;
use Illuminate\Support\Facades\Facade;
use Tochka\OpenRpc\Support\Context;

/**
 * @method static array generate(array $jsonRpcConfig, array $servers)
 * @method static SchemaReferenceInterface|null getSchemaWithPipes(Parameter $parameter, Context $context, bool $isResult = false)
 * @method static Components getComponents()
 * @see MethodDescriptionGenerator
 */
class MethodDescription extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
