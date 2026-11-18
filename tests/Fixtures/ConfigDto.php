<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Attributes\SchemaReference;

#[SchemaReference]
class ConfigDto
{
    public function __construct(
        public readonly int $sessionCounter,
        public readonly int $requestCount,
        /** @var array<int, int> */
        #[Examples([[30, 45, 60]])]
        public readonly array $times,
    ) {
    }
}
