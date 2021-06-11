<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\Schema;

interface SchemaReferenceInterface
{
    public function getSchema(): ?Schema;
}
