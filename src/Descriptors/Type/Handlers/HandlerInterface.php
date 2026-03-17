<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

interface HandlerInterface
{
    /**
     * @param SingularTypeInfo $info
     * @return bool
     */
    public function shouldHandle(SingularTypeInfo $info): bool;
    
    /**
     * @param SingularTypeInfo $info
     * @param Schema $schema
     * @param TypeDescriptor $typeDescriptor
     * @return Schema
     */
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema;
}
