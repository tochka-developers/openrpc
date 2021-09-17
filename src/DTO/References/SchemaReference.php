<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\DTO\Schema;

/**
 * @extends AbstractReference<Schema>
 */
final class SchemaReference extends AbstractReference implements SchemaReferenceInterface
{
    public function getPath(): string
    {
        return '#/components/schemas';
    }
    
    public function getSchema(): Schema
    {
        return $this->getItem();
    }
}
