<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\Tag;

interface TagReferenceInterface
{
    public function getTag(): Tag;
}
