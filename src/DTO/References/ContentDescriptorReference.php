<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\ContentDescriptorReferenceInterface;
use Tochka\OpenRpc\DTO\ContentDescriptor;

/**
 * @extends AbstractReference<ContentDescriptor>
 */
final class ContentDescriptorReference extends AbstractReference implements ContentDescriptorReferenceInterface
{
    protected function getPath(): string
    {
        return '#/components/contentDescriptors';
    }
    
    public function getContentDescriptor(): ContentDescriptor
    {
        return $this->getItem();
    }
}
