<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Tests\Fixtures\SharedData as DataAlias;

class UnionDto
{
    public SharedData|PlainData|null $value;
    /** @var ?DataAlias */
    public ?SharedData $nullable;
    public array $untyped;
    #[Examples([])]
    public string $emptyExamples;
}
