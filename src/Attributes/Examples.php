<?php

namespace Tochka\OpenRpc\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Examples
{
    /**
     * @param array<mixed> $values
     */
    public function __construct(
        public readonly array $values,
    ) {
    }
}
