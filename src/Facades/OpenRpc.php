<?php

namespace Tochka\OpenRpc\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static handle()
 */
class OpenRpc extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
