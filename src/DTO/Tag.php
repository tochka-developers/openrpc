<?php

namespace Tochka\OpenRpc\DTO;

/**
 * Adds metadata to a single tag that is used by the Method Object. It is not mandatory to have a Tag Object per
 * tag defined in the Method Object instances.
 */
final class Tag
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
   
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
