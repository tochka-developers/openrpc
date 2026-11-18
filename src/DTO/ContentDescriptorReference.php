<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ContentDescriptorReferenceInterface;

final class ContentDescriptorReference implements \JsonSerializable, ContentDescriptorReferenceInterface
{
    public function __construct(
        public readonly string $ref,
        private readonly ContentDescriptor $descriptor,
    ) {
    }

    public function getContentDescriptor(): ContentDescriptor
    {
        return $this->descriptor;
    }

    public function jsonSerialize(): object
    {
        return (object)['$ref' => $this->ref];
    }
}
