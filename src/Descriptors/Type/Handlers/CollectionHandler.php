<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use phpDocumentor\Reflection\PseudoTypes\Generic;
use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class CollectionHandler implements HandlerInterface
{
    public function shouldHandle(SingularTypeInfo $info): bool
    {
        return in_array(
            $info->className,
            [
                '\Illuminate\Support\Collection',
                '\Illuminate\Database\Eloquent\Collection'
            ]
        );
    }
    
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        if ($info->phpDocType instanceof Generic) {
            $types = $info->phpDocType->getTypes();
            $valueType = $types[1] ?? null;
            if ($valueType) {
                $schema->title = '';
                $schema->type = ['array'];
                $schema->items = $typeDescriptor->describeFromPHPDoc($valueType, new Schema());
            }
            
            return $schema;
        }
        
        $schema->type = ['array'];
        
        return $schema;
    }
}
