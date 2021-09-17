<?php

namespace Tochka\OpenRpc\Contracts;

use Tochka\OpenRpc\DTO\ContentDescriptor;

interface ContentDescriptorReferenceInterface
{
    public function getContentDescriptor(): ContentDescriptor;
}
