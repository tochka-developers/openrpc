<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Support\DataTransferObject;

final class ExternalDocumentation extends DataTransferObject
{
    /**
     * A verbose explanation of the target documentation. GitHub Flavored Markdown syntax MAY be used for rich text
     * representation.
     */
    public ?string $description;
    
    /**
     * REQUIRED. The URL for the target documentation. Value MUST be in the format of a URL
     */
    public string $url;
    
    public function __construct(string $url)
    {
        $this->url = $url;
    }
}
