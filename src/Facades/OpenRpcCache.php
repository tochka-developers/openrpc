<?php

namespace Tochka\OpenRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\OpenRpc\SchemaCache;

/**
 * @method static array|null get()
 * @method static set(array $schema)
 * @method static clear()
 * @see SchemaCache
 */
class OpenRpcCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
