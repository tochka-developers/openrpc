<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use DateTimeZone;
use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class TimeZoneHandler implements HandlerInterface
{
    public function shouldHandle(SingularTypeInfo $info): bool
    {
        return $info->className && is_subclass_of($info->className, DateTimeZone::class);
    }
    
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        $schema->type = ['string'];
        $schema->format = 'IANA';
        
        return $schema;
    }
}
