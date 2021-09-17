<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\LinkReferenceInterface;
use Tochka\OpenRpc\DTO\Link;

/**
 * @extends AbstractReference<Link>
 */
final class LinkReference extends AbstractReference implements LinkReferenceInterface
{
    protected function getPath(): string
    {
        return '#/components/links';
    }
    
    public function getLink(): Link
    {
        return $this->getItem();
    }
}
