<?php

namespace Tochka\OpenRpc\Descriptors\Type;

use phpDocumentor\Reflection\PseudoTypes\Generic;
use phpDocumentor\Reflection\Type;
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
use Tochka\OpenRpc\DTO\ContentDescriptor;
use Tochka\OpenRpc\DTO\ContentDescriptorReference;
use Tochka\OpenRpc\DTO\Schema;

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
    /** @var array<string, Schema> */
    protected array $schemas = [];
    /** @var array<string, ContentDescriptor> */
    protected array $contentDescriptors = [];

    public function describeProperty(\ReflectionProperty $property, ?Type $phpDocType): Schema
    {
        return $this->describe($property->getType(), new Schema(), $phpDocType);
    }

    public function reset(): void
    {
        $this->schemas = [];
        $this->contentDescriptors = [];
    }

    /**
     * @return array<string, Schema>
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * @return array<string, ContentDescriptor>
     */
    public function getContentDescriptors(): array
    {
        return $this->contentDescriptors;
    }

    public function referenceParameter(Schema $requestSchema, ContentDescriptor $descriptor): ContentDescriptor|ContentDescriptorReference
    {
        $schemaKey = array_search($requestSchema, $this->schemas, true);
        if ($schemaKey === false) {
            return $descriptor;
        }

        $key = $schemaKey . '.' . $descriptor->name;
        $this->contentDescriptors[$key] ??= $descriptor;

        return new ContentDescriptorReference(
            '#/components/contentDescriptors/' . str_replace(['~', '/'], ['~0', '~1'], $key),
            $this->contentDescriptors[$key],
        );
    }

    public function getPropertySchema(Schema $schema, string $propertyName): Schema
    {
        $key = array_search($schema, $this->schemas, true);
        if ($key === false) {
            return $schema->properties[$propertyName];
        }

        $reference = new Schema();
        $reference->ref = '#/components/schemas/'
            . str_replace(['~', '/'], ['~0', '~1'], $key)
            . '/properties/' . str_replace(['~', '/'], ['~0', '~1'], $propertyName);

        return $reference;
    }

    public function resolveSchema(Schema $schema): Schema
    {
        if (isset($schema->ref)) {
            $key = str_replace(['~1', '~0'], ['/', '~'], substr($schema->ref, strlen('#/components/schemas/')));
            return $this->schemas[$key];
        }
        foreach ($schema->anyOf ?? [] as $branch) {
            $resolved = $this->resolveSchema($branch);
            if (isset($resolved->properties)) {
                return $resolved;
            }
        }

        return $schema;
    }

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
            return $this->describeFromPHPDoc($phpDocType, $schema);
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return $schema;
        }

        if ($type instanceof \ReflectionNamedType) {
            if ($type->getName() === 'mixed') {
                return $schema;
            }

            if ($type->getName() === 'array') {
                $schema->type = $type->allowsNull() ? ['array', 'null'] : ['array'];

                return $schema;
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
                foreach ($type->getTypes() as $reflectionType) {
                    $schema->anyOf[] = $this->describe($reflectionType, new Schema());
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
        $className = ltrim($info->className ?? '', '\\');
        $reflection = class_exists($className) ? new \ReflectionClass($className) : null;
        $className = $reflection?->getName() ?? $className;
        $key = str_replace('\\', '.', $className);
        if ($info->phpDocType instanceof Generic) {
            $key .= '.' . hash('sha256', (string)$info->phpDocType);
        }
        $schema->ref = '#/components/schemas/' . str_replace(['~', '/'], ['~0', '~1'], $key);
        if (isset($this->schemas[$key])) {
            return $schema;
        }

        $savedSchemas = $this->schemas;
        $definition = new Schema();
        $definition->title = $className;
        $this->schemas[$key] = $definition;
        try {
            foreach ($this->handlers as $handler) {
                if ($handler->shouldHandle($info)) {
                    $definition->type = ['object'];
                    $definition = $handler->handle($info, $definition, $this);
                    break;
                }
            }
            $this->schemas[$key] = $definition;

            return $schema;
        } catch (\Throwable $exception) {
            $this->schemas = $savedSchemas;

            throw $exception;
        }
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
            $nullSchema = new Schema();
            $nullSchema->type = ['null'];
            $schema->anyOf = [$this->describeFromPHPDoc($phpDocType->getActualType(), new Schema()), $nullSchema];
            return $schema;
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
