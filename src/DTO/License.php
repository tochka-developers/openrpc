<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\DataTransferObject;

/**
 * License information for the exposed API.
 */
class License extends DataTransferObject
{
    /**
     * REQUIRED. The license name used for the API.
     */
    public string $name;
    
    /**
     * A URL to the license used for the API. MUST be in the format of a URL.
     */
    public string $url;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
