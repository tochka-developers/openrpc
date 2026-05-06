<?php

namespace Tochka\OpenRpc\Descriptors\Type;

use phpDocumentor\Reflection\PseudoTypes\Generic;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;
use phpDocumentor\Reflection\Types\Void_;
use Tochka\OpenRpc\Descriptors\Type\Handlers\HandlerInterface;
use Tochka\OpenRpc\DTO\Schema;
use phpDocumentor\Reflection\Type;

class TypeDescriptor
{
    public const PRIMITIVE_DOC_TYPES = [
        Integer::class,
        Float_::class,
        Null_::class,
        Void_::class,
        String_::class,
        Boolean::class,
    ];
    
    /** @var array<HandlerInterface> */
    protected array $handlers = [];
    /** @var array<int, string> */
    protected array $classStack = [];

    public function __construct(?HandlerInterface ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function addHandler(HandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    public function describe(?\ReflectionType $type, Schema $schema, ?Type $phpDocType = null): Schema
    {
        if ($phpDocType) {
            $this->describeFromPHPDoc($phpDocType, $schema);

            return $schema;
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return $schema;
        }

        if ($type instanceof \ReflectionNamedType) {
            if ($type->getName() === 'mixed') {
                return $schema;
            }

            if ($type->getName() === 'array') {
                return $this->describeFromPHPDoc($phpDocType, $schema);
            }

            if ($type->isBuiltin()) {
                $schema->type = [$type->getName()];
                if ($type->allowsNull()) {
                    $schema->type = [...$schema->type, 'null'];
                }

                return $schema;
            }

            if (class_exists($type->getName())) {
                $schema = $this->handleClassByName(new SingularTypeInfo($type->getName(), $type, $phpDocType), $schema);
                if ($type->allowsNull()) {
                    $anyOfSchema = new Schema();
                    $nullSchema = new Schema();
                    $nullSchema->type = ['null'];
                    $anyOfSchema->anyOf = [$nullSchema, $schema];

                    return $anyOfSchema;
                }

                return $schema;
            }

            return $schema;
        }

        if ($type instanceof \ReflectionUnionType) {
            if ($this->allTypesIsPrimitive($type)) {
                foreach ($type->getTypes() as $t) {
                    $schema->type = [...$schema->type, $t->getName()];
                }
                if ($type->allowsNull()) {
                    $schema->type = [...$schema->type, 'null'];
                }
            } else {
                foreach ($type->getTypes() as $type) {
                    return $this->describe($type, $schema);
                }
            }
        }

        return $schema;
    }

    protected function allTypesIsPrimitive(\ReflectionUnionType $type): bool
    {
        foreach ($type->getTypes() as $t) {
            if (!$t->isBuiltin()) {
                return false;
            }
        }

        return true;
    }

    protected function handleClassByName(SingularTypeInfo $info, Schema $schema): Schema
    {
        $schema->title = $info->className;
        if (\in_array($info->className, $this->classStack, true)) {
            $schema->type = ['object'];

            return $schema;
        }

        $this->classStack[] = $info->className;

        foreach ($this->handlers as $handler) {
            if ($handler->shouldHandle($info)) {
                $schema->type = ['object'];

                $result = $handler->handle($info, $schema, $this);
                array_pop($this->classStack);

                return $result;
            }
        }

        return $schema;
    }

    public function describeFromPHPDoc(?Type $phpDocType, Schema $schema): Schema
    {
        if (!$phpDocType) {
            return $schema;
        }
        
        if ($this->allDocTypesIsPrimitive([$phpDocType])) {
            $schema->type = [(string)$phpDocType];
            
            return $schema;
        }
        if ($phpDocType instanceof Compound) {
            // if all present type is primitive use array of types, else use anyOf
            if ($this->allDocTypesIsPrimitive($phpDocType->getIterator()->getArrayCopy())) {
                foreach ($phpDocType->getIterator() as $localType) {
                    $schema->type = [...$schema->type, (string)$localType];
                }
            } else {
                foreach ($phpDocType->getIterator() as $type) {
                    $schema->anyOf[] = $this->describeFromPHPDoc($type, new Schema());
                }
            }

            return $schema;
        }

        if ($phpDocType instanceof Nullable) {
            $schema->type = [...$schema->type, 'null'];

            return $this->describeFromPHPDoc($phpDocType->getActualType(), $schema);
        }

        if ($phpDocType instanceof Generic) {
            $singularType = new SingularTypeInfo($phpDocType->getFqsen(), null, $phpDocType);

            return $this->handleClassByName($singularType, $schema);
        }

        if ($phpDocType instanceof Object_) {
            // кейс когда указан не класс, а просто object
            if ($phpDocType->getFqsen() === null) {
                $schema->type = ['object'];

                return $schema;
            }

            return $this->handleClassByName(new SingularTypeInfo($phpDocType->getFqsen(), null, $phpDocType), $schema);
        }


        if ($phpDocType instanceof Array_) {
            $schema->type = ['array'];
            $schema->items = $this->describeFromPHPDoc($phpDocType->getValueType(), new Schema());

            return $schema;
        }

        return $schema;
    }
    
    /**
     * @param array<int, Type> $types
     * @return bool
     */
    protected function allDocTypesIsPrimitive(array $types): bool
    {
        return array_all($types, fn($t) => \in_array($t::class, self::PRIMITIVE_DOC_TYPES));
    }
}
