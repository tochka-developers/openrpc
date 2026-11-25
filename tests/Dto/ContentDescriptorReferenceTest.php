<?php

namespace Tochka\OpenRpc\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\DTO\ContentDescriptor;
use Tochka\OpenRpc\DTO\ContentDescriptorReference;
use Tochka\OpenRpc\DTO\Schema;

#[CoversClass(ContentDescriptorReference::class)]
final class ContentDescriptorReferenceTest extends TestCase
{
    /** References retain their descriptor internally and serialize only the pointer. */
    public function testReferenceSerialization(): void
    {
        $descriptor = new ContentDescriptor('phone', new Schema());
        $reference = new ContentDescriptorReference('#/components/contentDescriptors/Request.phone', $descriptor);
        self::assertSame($descriptor, $reference->getContentDescriptor());
        self::assertSame(
            ['$ref' => '#/components/contentDescriptors/Request.phone'],
            json_decode(json_encode($reference, JSON_THROW_ON_ERROR), true),
        );
    }
}
