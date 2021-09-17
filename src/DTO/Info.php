<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * The object provides metadata about the API. The metadata MAY be used by the clients if needed, and MAY be
 * presented in editing or documentation generation tools for convenience.
 */
final class Info extends DataTransferObject
{
    /**
     * REQUIRED. The title of the application.
     */
    public string $title;
    
    /**
     * A verbose description of the application. GitHub Flavored Markdown syntax MAY be used for rich text
     * representation.
     */
    public ?string $description;
    
    /**
     * A URL to the Terms of Service for the API. MUST be in the format of a URL.
     */
    public ?string $termsOfService;
    
    /**
     * The contact information for the exposed API.
     */
    public ?Contact $contact;
    
    /**
     * The license information for the exposed API.
     */
    public ?License $license;
    
    /**
     * REQUIRED. The version of the OpenRPC document (which is distinct from the OpenRPC Specification version
     * or the API implementation version).
     */
    public string $version;
    
    public function __construct(string $title, string $version)
    {
        $this->title = $title;
        $this->version = $version;
    }
}
