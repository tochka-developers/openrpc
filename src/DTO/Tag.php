<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\TagReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * Adds metadata to a single tag that is used by the Method Object. It is not mandatory to have a Tag Object per
 * tag defined in the Method Object instances.
 */
final class Tag extends DataTransferObject implements TagReferenceInterface
{
    /**
     * REQUIRED. The name of the tag.
     */
    public string $name;
    
    /**
     * A short summary of the tag.
     */
    public ?string $summary;
    
    /**
     * A verbose explanation for the tag. GitHub Flavored Markdown syntax MAY be used for rich text representation.
     */
    public ?string $description;
    
    /**
     * Additional external documentation for this tag.
     */
    public ?ExternalDocumentation $externalDocs;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getTag(): Tag
    {
        return $this;
    }
}
