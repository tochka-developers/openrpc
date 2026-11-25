<?php

namespace Tochka\OpenRpc\Tests\Descriptors\Type\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\Tests\Fixtures\ResponseDto;
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
}
