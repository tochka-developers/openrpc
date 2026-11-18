<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Attributes\SchemaReference;


#[SchemaReference]
class ResponseDto
{
    public function __construct(
        /** Phone description. */
        public readonly string $phone,
        public readonly ?int $otpNumber,
        /** @var ConfigDto|null */
        public readonly ?ConfigDto $config,
        #[Examples([false, true])]
        public readonly bool $enabled = false,
        public readonly int $count = 0,
    ) {
    }
}
