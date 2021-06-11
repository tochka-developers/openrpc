<?php

namespace Tochka\OpenRpc\Facades;

use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\DTO\Schema;
use Tochka\OpenRpc\ExpectedPipeObject;
use Tochka\OpenRpc\MethodDescriptionGenerator;
use Tochka\OpenRpc\VarType;
use Illuminate\Support\Facades\Facade;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\Type;

/**
 * @method static array generate(array $jsonRpcConfig, array $servers)
 * @method static SchemaReferenceInterface getParametersFromDTO(Schema $schema, string $className)
 * @method static Property[]|PropertyWrite[]|PropertyRead[] getPropertyTagsFromDTO(\ReflectionClass $reflectionClass)
 * @method static ExpectedPipeObject sendThroughPipes(?\Reflector $reflector, SchemaReferenceInterface $schema, VarType $varType, ?DocBlock $docBlock = null)
 * @method static VarType|null getVarTypesFromPhpDocType(Type $type, ?VarType $varType = null)
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
