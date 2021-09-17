<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * An object representing a Server.
 */
final class Server extends DataTransferObject
{
    /**
     * REQUIRED. A name to be used as the canonical name for the server.
     */
    public string $name;
    
    /**
     * REQUIRED. A URL to the target host. This URL supports Server Variables and MAY be relative, to indicate
     * that the host location is relative to the location where the OpenRPC document is being served. Server
     * Variables are passed into the Runtime Expression to produce a server URL.
     */
    public string $url;
    
    /**
     * A short summary of what the server is.
     */
    public ?string $summary;
    
    /**
     * An optional string describing the host designated by the URL. GitHub Flavored Markdown syntax MAY be used
     * for rich text representation.
     */
    public ?string $description;
    
    /**
     * A map between a variable name and its value. The value is passed into the Runtime Expression to produce a
     * server URL.
     * @var array<string, ServerVariable>
     */
    public array $variables;
    
    public function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;
    }
}
