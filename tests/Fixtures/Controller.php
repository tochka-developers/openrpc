<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

class Controller
{
    /** Expose scalar parameters for checking their declared order. */
    public function ordinary(string $second, string $first): string
    {
        throw new \LogicException('Not invoked.');
    }

    public function optional(OptionalDto $request): OptionalDto
    {
        throw new \LogicException('Not invoked.');
    }

    public function optionalReference(ReferencedOptionalDto $request): ReferencedOptionalDto
    {
        throw new \LogicException('Not invoked.');
    }

    public function inline(PlainRequest $request): PlainData
    {
        throw new \LogicException('Not invoked.');
    }

    public function nullable(?ReferencedRequest $request = null): ResponseDto
    {
        throw new \LogicException('Not invoked.');
    }

    /**
     * Verify
     * @param ReferencedRequest $request
     * @return ResponseDto
     */
    public function verify(ReferencedRequest $request): ResponseDto
    {
        throw new \LogicException('Not invoked.');
    }
}
