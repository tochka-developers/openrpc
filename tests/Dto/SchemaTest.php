<?php

namespace Tochka\OpenRpc\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\DTO\Schema;

#[CoversClass(Schema::class)]
final class SchemaTest extends TestCase
{
    /** JSON uses the standard reference keyword and preserves falsy metadata. */
    public function testReferenceSerialization(): void
    {
        $schema = new Schema();
        $schema->ref = '#/components/schemas/Example';
        $schema->default = null;
        $schema->examples = [null, false, 0, [], ''];
        $json = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), true);
        self::assertSame('#/components/schemas/Example', $json['allOf'][0]['$ref']);
        self::assertArrayNotHasKey('ref', $json);
        self::assertArrayNotHasKey('type', $json);
        self::assertArrayHasKey('default', $json);
        self::assertNull($json['default']);
        self::assertSame([null, false, 0, [], ''], $json['examples']);
    }
}
