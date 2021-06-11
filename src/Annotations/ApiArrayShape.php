<?php

namespace Tochka\OpenRpc\Annotations;

/**
 * @Annotation
 * @Target({"METHOD", "PROPERTY", "CLASS"})
 */
class ApiArrayShape
{
    public array $shape;
}
