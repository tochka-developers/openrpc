<?php

namespace Tochka\OpenRpc\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\DTO\Schema;

#[CoversClass(Components::class)]
final class ComponentsTest extends TestCase
{
    /** Empty maps are omitted and schema maps serialize as JSON objects. */
    public function testComponentMaps(): void
    {
        $components = new Components();
        self::assertSame('{}', json_encode($components, JSON_THROW_ON_ERROR));
        $schema = new Schema();
        $schema->type = ['object'];
        $schema->properties = [];
        $components->schemas['Example'] = $schema;
        self::assertSame('{"schemas":{"Example":{"properties":{},"type":"object"}}}', json_encode($components, JSON_THROW_ON_ERROR));
    }
}
