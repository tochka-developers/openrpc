<?php

namespace Tochka\OpenRpc\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiIgnoreMethod
{
    public string $name;
}
