<?php

namespace Tochka\OpenRpc\Descriptors\Type;

use phpDocumentor\Reflection\Type;

class SingularTypeInfo
{
    public function __construct(
        public readonly ?string $className = null,
        public readonly ?\ReflectionType $type = null,
        public readonly ?Type $phpDocType = null
    ) {
    }
}
