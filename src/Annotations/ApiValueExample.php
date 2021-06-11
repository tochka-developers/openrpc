<?php

namespace Tochka\OpenRpc\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ApiValueExample
{
    public array $examples;
}
