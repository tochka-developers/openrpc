<?php

namespace Tochka\OpenRpc\Tests\Descriptors\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\Tests\Fixtures;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types\Object_;
use Tochka\OpenRpc\Tests\Fixtures\BrokenReference;
use Tochka\OpenRpc\Tests\Fixtures\ChildRequest;
use Tochka\OpenRpc\Tests\Fixtures\InlineA;
use Tochka\OpenRpc\Tests\Fixtures\InlineCycle;
use Tochka\OpenRpc\Tests\Fixtures\MixedA;
use Tochka\OpenRpc\Tests\Fixtures\MixedB;
use Tochka\OpenRpc\Tests\Fixtures\PlainRequest;
use Tochka\OpenRpc\Tests\Fixtures\ReferenceCycle;
use Tochka\OpenRpc\Tests\Fixtures\RequestDto;
use Tochka\OpenRpc\Tests\Fixtures\SharedData;
use Tochka\OpenRpc\Tests\Fixtures\UnionDto;
use Tochka\OpenRpc\Descriptors\Type\Handlers\ObjectHandler;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

#[CoversClass(TypeDescriptor::class)]
final class TypeDescriptorTest extends TestCase
{
    private TypeDescriptor $descriptor;

    /** Create an isolated descriptor for each test. */
    protected function setUp(): void
    {
        $this->descriptor = new TypeDescriptor(new ObjectHandler());
    }

    /** Describe and serialize a real reflected DTO. */
    private function describe(string $class): array
    {
        $schema = $this->descriptor->describeFromPHPDoc(new Object_(new Fqsen('\\' . $class)), new Schema());

        self::assertSame('#/components/schemas/' . str_replace('\\', '.', $class), $schema->ref);

        return json_decode(json_encode($this->descriptor->resolveSchema($schema), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }

    /** Objects reuse definitions, including repeated and nullable properties. */
    public function testObjectReferences(): void
    {
        $schema = $this->describe(PlainRequest::class);
        self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.PlainData', $schema['properties']['data']['anyOf'][0]['$ref']);
        self::assertSame($schema['properties']['first'], $schema['properties']['second']);
        self::assertCount(2, $this->descriptor->getSchemas());
    }

    /** Imports resolve to one reusable definition without leaking property annotations. */
    public function testReferencesAndMetadata(): void
    {
        $schema = $this->describe(RequestDto::class);
        $ref = '#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.SharedData';
        self::assertSame($ref, $schema['properties']['data']['anyOf'][0]['$ref']);
        self::assertSame($ref, $schema['properties']['list']['items']['$ref']);
        self::assertSame(['first'], $schema['properties']['first']['examples']);
        self::assertSame(['second'], $schema['properties']['second']['examples']);
        self::assertSame('Data description.', $schema['properties']['data']['description']);
        self::assertArrayNotHasKey('ignored', $schema['properties']);
        self::assertCount(2, $this->descriptor->getSchemas());
        $definition = json_decode(json_encode($this->descriptor->getSchemas()['Tochka.OpenRpc.Tests.Fixtures.SharedData']), true);
        self::assertArrayNotHasKey('examples', $definition);
        self::assertSame([null, true, false, 0, [30, 45]], $definition['properties']['agree']['examples']);
        self::assertSame(['boolean', 'null'], $definition['properties']['agree']['type']);
    }

    /** Both entry points of a mixed cycle produce finite reusable definitions. */
    public function testMixedAndReferenceCycles(): void
    {
        foreach ([MixedA::class, MixedB::class, ReferenceCycle::class] as $class) {
            $this->descriptor->reset();
            $this->describe($class);
            self::assertCount($class === ReferenceCycle::class ? 1 : 2, $this->descriptor->getSchemas());
            self::assertNotFalse(json_encode($this->descriptor->getSchemas(), JSON_THROW_ON_ERROR));
        }
    }

    /** Cycles without attributes terminate through registered references. */
    public function testCyclesWithoutAttributes(): void
    {
        foreach ([InlineCycle::class, InlineA::class, BrokenReference::class] as $class) {
            $this->descriptor->reset();
            $schema = $this->describe($class);
            self::assertSame('object', $schema['type']);
            self::assertCount($class === InlineCycle::class ? 1 : 2, $this->descriptor->getSchemas());
            if ($class === InlineCycle::class) {
                self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.InlineCycle', $schema['properties']['child']['anyOf'][1]['$ref']);
            } elseif ($class === InlineA::class) {
                self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.InlineB', $schema['properties']['children']['items']['$ref']);
                $child = $this->descriptor->getSchemas()['Tochka.OpenRpc.Tests.Fixtures.InlineB'];
                self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.InlineA', $child->properties['parent']->ref);
            }
        }
    }

    /** Native unions, nullable PHPDoc and arrays retain all branches. */
    public function testUnionAndNullableTypes(): void
    {
        $schema = $this->describe(UnionDto::class);
        self::assertCount(3, $schema['properties']['value']['anyOf']);
        self::assertSame('null', $schema['properties']['nullable']['anyOf'][1]['type']);
        self::assertSame('array', $schema['properties']['untyped']['type']);
        self::assertSame([], $schema['properties']['emptyExamples']['examples']);
    }

    /** Full class keys avoid collisions and inherited DTOs have their own definitions. */
    public function testKeysInheritanceAndReset(): void
    {
        $this->describe(SharedData::class);
        $this->describe(Fixtures\Other\SharedData::class);
        self::assertCount(2, $this->descriptor->getSchemas());
        $this->descriptor->reset();
        $schema = $this->describe(ChildRequest::class);
        self::assertArrayNotHasKey('$ref', $schema);
        self::assertSame(['Tochka.OpenRpc.Tests.Fixtures.ChildRequest', 'Tochka.OpenRpc.Tests.Fixtures.SharedData'], array_keys($this->descriptor->getSchemas()));
    }

    /** Missing handlers still reuse a stable registered definition. */
    public function testMissingHandlerCleanup(): void
    {
        $this->descriptor = new TypeDescriptor();
        self::assertSame($this->describe(PlainRequest::class), $this->describe(PlainRequest::class));
    }

    /** Failed definitions preserve completed components and discard partial ones. */
    public function testRollbackPreservesExistingSchemas(): void
    {
        $this->describe(SharedData::class);
        $before = json_encode($this->descriptor->getSchemas(), JSON_THROW_ON_ERROR);
        try {
            $this->describe(Fixtures\NullOnlyOptionalDto::class);
            self::fail('Expected invalid Optional exception.');
        } catch (\RuntimeException) {
            self::assertSame($before, json_encode($this->descriptor->getSchemas(), JSON_THROW_ON_ERROR));
        }
    }

    /** Generic arguments remain distinct for handlers that specialize schemas. */
    public function testGenericReferenceKeys(): void
    {
        $handler = $this->createMock(\Tochka\OpenRpc\Descriptors\Type\Handlers\HandlerInterface::class);
        $handler->method('shouldHandle')->willReturn(true);
        $handler->expects($this->exactly(2))->method('handle')->willReturnCallback(
            static function (\Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo $info, Schema $schema, TypeDescriptor $descriptor): Schema {
                $schema->type = ['array'];
                $schema->items = $descriptor->describeFromPHPDoc($info->phpDocType->getTypes()[0], new Schema());
                return $schema;
            }
        );
        $descriptor = new TypeDescriptor($handler);
        $references = [];
        foreach ([new \phpDocumentor\Reflection\Types\Integer(), new \phpDocumentor\Reflection\Types\String_()] as $type) {
            $generic = new \phpDocumentor\Reflection\PseudoTypes\Generic(new Fqsen('\\' . SharedData::class), [$type]);
            $references[] = $descriptor->describeFromPHPDoc($generic, new Schema())->ref;
        }
        self::assertNotSame($references[0], $references[1]);
        $definitions = array_values($descriptor->getSchemas());
        self::assertSame(['int'], $definitions[0]->items->type);
        self::assertSame(['string'], $definitions[1]->items->type);
    }

    /** Handler failures leave no partial reference. */
    public function testHandlerExceptionCleanup(): void
    {
        $handler = $this->createMock(\Tochka\OpenRpc\Descriptors\Type\Handlers\HandlerInterface::class);
        $handler->method('shouldHandle')->willReturn(true);
        $handler->method('handle')->willThrowException(new \RuntimeException('Test failure'));
        $this->descriptor = new TypeDescriptor($handler);
        try {
            $this->describe(SharedData::class);
            self::fail('Expected handler exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Test failure', $exception->getMessage());
            self::assertSame([], $this->descriptor->getSchemas());
        }
    }

    /** Nested generic collections have distinct reusable array definitions. */
    public function testNestedGenericCollections(): void
    {
        $descriptor = new TypeDescriptor(new \Tochka\OpenRpc\Descriptors\Type\Handlers\CollectionHandler());
        $collection = new Fqsen('\\Illuminate\\Support\\Collection');
        $inner = new \phpDocumentor\Reflection\PseudoTypes\Generic($collection, [
            new \phpDocumentor\Reflection\Types\Integer(),
            new \phpDocumentor\Reflection\Types\String_(),
        ]);
        $outer = new \phpDocumentor\Reflection\PseudoTypes\Generic($collection, [
            new \phpDocumentor\Reflection\Types\Integer(),
            $inner,
        ]);
        $schema = $descriptor->describeFromPHPDoc($outer, new Schema());
        self::assertStringStartsWith('#/components/schemas/Illuminate.Support.Collection.', $schema->ref);
        $outerSchema = $descriptor->resolveSchema($schema);
        self::assertSame(['array'], $outerSchema->type);
        $innerSchema = $descriptor->resolveSchema($outerSchema->items);
        self::assertSame(['array'], $innerSchema->type);
        self::assertSame(['string'], $innerSchema->items->type);
        self::assertCount(2, $descriptor->getSchemas());
    }

    /** Property references escape JSON Pointer segments and leave inline schemas intact. */
    public function testPropertySchemaReferences(): void
    {
        $this->describe(SharedData::class);
        $component = $this->descriptor->getSchemas()['Tochka.OpenRpc.Tests.Fixtures.SharedData'];
        $property = new Schema();
        $property->type = ['string'];
        $component->properties['path/~value'] = $property;
        $reference = $this->descriptor->getPropertySchema($component, 'path/~value');
        self::assertSame(
            '#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.SharedData/properties/path~1~0value',
            $reference->ref,
        );
        self::assertSame($property, $component->properties['path/~value']);
        $inline = clone $component;
        self::assertSame($property, $this->descriptor->getPropertySchema($inline, 'path/~value'));
    }

    /** Reusable parameters are isolated by full DTO identity and cleared between documents. */
    public function testContentDescriptorRegistry(): void
    {
        $this->describe(SharedData::class);
        $this->describe(Fixtures\Other\SharedData::class);
        $schemas = $this->descriptor->getSchemas();
        $first = new \Tochka\OpenRpc\DTO\ContentDescriptor('value', new Schema());
        $reference = $this->descriptor->referenceParameter($schemas['Tochka.OpenRpc.Tests.Fixtures.SharedData'], $first);
        $duplicate = $this->descriptor->referenceParameter(
            $schemas['Tochka.OpenRpc.Tests.Fixtures.SharedData'],
            new \Tochka\OpenRpc\DTO\ContentDescriptor('value', new Schema()),
        );
        self::assertSame($first, $duplicate->getContentDescriptor());
        self::assertSame($reference->ref, $duplicate->ref);
        $other = $this->descriptor->referenceParameter($schemas['Tochka.OpenRpc.Tests.Fixtures.Other.SharedData'], $first);
        self::assertNotSame($reference->ref, $other->ref);
        self::assertCount(2, $this->descriptor->getContentDescriptors());
        self::assertSame($first, $this->descriptor->referenceParameter(new Schema(), $first));
        $this->descriptor->reset();
        self::assertSame([], $this->descriptor->getContentDescriptors());
    }
}
