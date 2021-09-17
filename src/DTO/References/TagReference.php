<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\TagReferenceInterface;
use Tochka\OpenRpc\DTO\Tag;

/**
 * @extends AbstractReference<Tag>
 */
final class TagReference extends AbstractReference implements TagReferenceInterface
{
    public function getPath(): string
    {
        return '#/components/tags';
    }
    
    public function getTag(): Tag
    {
        return $this->getItem();
    }
}
