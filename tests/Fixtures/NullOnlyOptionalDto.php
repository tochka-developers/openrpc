<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Optional;

class NullOnlyOptionalDto
{
    #[Optional]
    public null $value = null;
}
