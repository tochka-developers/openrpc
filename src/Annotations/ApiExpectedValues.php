<?php

namespace Tochka\OpenRpc\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ApiExpectedValues
{
    public array $values = [];
}
