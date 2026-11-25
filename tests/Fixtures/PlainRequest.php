<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

class PlainRequest
{
    /** @var PlainData|null */
    public ?PlainData $data = null;
    public PlainData $first;
    public PlainData $second;
}
