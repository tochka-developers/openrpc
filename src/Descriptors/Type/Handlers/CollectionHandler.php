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
        return $info->className === '\Illuminate\Support\Collection';
    }
    
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        if ($info->phpDocType instanceof Generic) {
            $types = $info->phpDocType->getTypes();
            $valueType = $types[1];
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
