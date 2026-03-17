<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class EnumHandler implements HandlerInterface
{
    public function shouldHandle(SingularTypeInfo $info): bool
    {
        return $info->className && enum_exists($info->className);
    }
    
    /**
     * @throws \ReflectionException
     */
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        /** @var \BackedEnum $className */
        $className = $info->className;
        $schema->enum = $className::cases();
        
        $reflector = new \ReflectionEnum($className);
        $valueType = $reflector->getBackingType();
        if ($valueType === null) {
            $schema->type = ['string', 'integer'];
        } else {
            $schema->type = [$valueType->getName()];
        }
        
        return $schema;
    }
}
