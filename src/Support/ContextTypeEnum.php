<?php

namespace Tochka\OpenRpc\Support;

use BenSampo\Enum\Enum;

/**
 * @method static self TYPE_METHOD()
 * @method static self TYPE_CLASS()
 * @method static self TYPE_VIRTUAL()
 */
final class ContextTypeEnum extends Enum
{
    public const TYPE_METHOD = 'method';
    public const TYPE_CLASS = 'class';
    public const TYPE_VIRTUAL = 'virtual';
}
