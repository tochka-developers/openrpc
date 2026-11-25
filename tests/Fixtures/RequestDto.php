<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Tests\Fixtures\SharedData as DataAlias;

class RequestDto
{
    /**
     * Phone
     *
     * Phone description.
     * @var string
     */
    public string $phone;

    /**
     * Data
     *
     * Data description.
     * @var DataAlias|null
     */
    public ?SharedData $data = null;

    /** @var array<int, DataAlias> */
    public array $list;

    #[Examples(['first'])]
    public SharedData $first;

    #[Examples(['second'])]
    public SharedData $second;

    public static string $ignored;
}
