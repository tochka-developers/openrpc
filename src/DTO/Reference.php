<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * A simple object to allow referencing other components in the specification, internally and externally.
 * The Reference Object is defined by JSON Schema and follows the same structure, behavior and rules.
 */
final class Reference extends DataTransferObject
{
    /**
     * REQUIRED. The reference string.
     */
    public string $ref;
    
    public function __construct(string $ref)
    {
        $this->ref = $ref;
    }
    
    public function toArray(): array
    {
        return [
            '$ref' => $this->ref,
        ];
    }
}
