<?php

namespace Tochka\OpenRpc\Tests\Descriptors\Type\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\Tests\Fixtures\NullOnlyOptionalDto;
use Tochka\OpenRpc\Tests\Fixtures\OptionalDto;
use Tochka\OpenRpc\Tests\Fixtures\ResponseDto;
use Tochka\OpenRpc\Tests\Fixtures\ConfigDto;
use Tochka\OpenRpc\Descriptors\Type\Handlers\ObjectHandler;
use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

#[CoversClass(ObjectHandler::class)]
final class ObjectHandlerTest extends TestCase
{
    /** Promoted properties preserve documentation, defaults and array examples. */
    public function testPromotedMetadata(): void
    {
        $handler = new ObjectHandler();
        $descriptor = new TypeDescriptor($handler);
        $schema = $handler->handle(new SingularTypeInfo(ResponseDto::class), new Schema(), $descriptor);
        $json = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), true);
        self::assertSame(['phone', 'otpNumber', 'config'], $json['required']);
        self::assertFalse($json['properties']['enabled']['default']);
        self::assertSame(0, $json['properties']['count']['default']);
        self::assertSame([false, true], $json['properties']['enabled']['examples']);
        self::assertSame('Phone description.', $json['properties']['phone']['title']);
        $config = json_decode(json_encode($descriptor->getSchemas()['Tochka.OpenRpc.Tests.Fixtures.ConfigDto']), true);
        self::assertSame('integer', $config['properties']['times']['items']['type']);
        self::assertSame([[30, 45, 60]], $config['properties']['times']['examples']);
        self::assertArrayNotHasKey('examples', $config['properties']['times']['items']);
    }

    /** Optional excludes null even with a null default, without changing nested schemas. */
    public function testOptionalProperties(): void
    {
        $handler = new ObjectHandler();
        $descriptor = new TypeDescriptor($handler);
        $schema = $handler->handle(new SingularTypeInfo(OptionalDto::class), new Schema(), $descriptor);
        $json = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), true);
        self::assertSame(['requiredNullable', 'requiredCount'], $json['required']);
        self::assertSame('string', $json['properties']['note']['type']);
        self::assertSame('string', $json['properties']['label']['type']);
        self::assertArrayNotHasKey('default', $json['properties']['label']);
        self::assertArrayNotHasKey('anyOf', $json['properties']['values']);
        self::assertSame('array', $json['properties']['values']['type']);
        self::assertSame(['string', 'null'], $json['properties']['values']['items']['type']);
        self::assertEqualsCanonicalizing(['string', 'integer'], $json['properties']['union']['type']);
        self::assertSame('integer', $json['properties']['count']['type']);
        self::assertArrayNotHasKey('default', $json['properties']['note']);
        self::assertArrayNotHasKey('default', $json['properties']['count']);
        self::assertFalse($json['properties']['enabled']['default']);
        self::assertArrayNotHasKey('anyOf', $json['properties']['nested']);
        self::assertArrayNotHasKey('anyOf', $json['properties']['shared']);
        self::assertSame('Shared details', $json['properties']['shared']['title']);
        self::assertSame('Optional shared description.', $json['properties']['shared']['description']);
        self::assertSame([['agree' => true]], $json['properties']['shared']['examples']);
        self::assertCount(2, $json['properties']['choice']['anyOf']);
        self::assertSame('object', $json['properties']['choice']['anyOf'][0]['type']);
        self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.SharedData', $json['properties']['choice']['anyOf'][1]['$ref']);
        self::assertArrayNotHasKey('default', $json['properties']['nested']);
        self::assertArrayNotHasKey('default', $json['properties']['shared']);
        self::assertSame(['string', 'null'], $json['properties']['requiredNullable']['type']);
        self::assertSame('integer', $json['properties']['nested']['properties']['value']['type']);
        self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.SharedData', $json['properties']['shared']['allOf'][0]['$ref']);
        $shared = json_decode(json_encode($descriptor->getSchemas()['Tochka.OpenRpc.Tests.Fixtures.SharedData'], JSON_THROW_ON_ERROR), true);
        self::assertSame(['boolean', 'null'], $shared['properties']['agree']['type']);
        self::assertNull($shared['properties']['agree']['default']);
    }

    /** Optional mixed stays unrestricted while explicit nullable PHPDoc loses null. */
    public function testOptionalMixedAllowsNull(): void
    {
        $handler = new ObjectHandler();
        $schema = $handler->handle(new SingularTypeInfo(OptionalDto::class), new Schema(), new TypeDescriptor($handler));
        $json = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        foreach (['mixed', 'untyped', 'documentedMixed', 'mixedDefault', 'promotedMixed'] as $name) {
            self::assertNotContains($name, $json['required']);
            self::assertSame([], $json['properties'][$name]);
            self::assertSame('{}', json_encode($schema->properties[$name], JSON_THROW_ON_ERROR));
        }
        self::assertSame('string', $json['properties']['documentedNullable']['type']);
        self::assertArrayNotHasKey('default', $json['properties']['documentedNullable']);
    }

    /** A null-only property cannot describe a present value under Optional. */
    public function testOptionalRejectsNullOnlyType(): void
    {
        $handler = new ObjectHandler();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Свойство с #[Optional] должно иметь тип, отличный от null.');
        $handler->handle(
            new SingularTypeInfo(NullOnlyOptionalDto::class),
            new Schema(),
            new TypeDescriptor($handler),
        );
    }
}
