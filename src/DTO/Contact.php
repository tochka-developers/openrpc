<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * Contact information for the exposed API.
 */
final class Contact extends DataTransferObject
{
    /**
     * The identifying name of the contact person/organization.
     */
    public string $name;
    
    /**
     * The URL pointing to the contact information. MUST be in the format of a URL.
     */
    public string $url;
    
    /**
     * The email address of the contact person/organization. MUST be in the format of an email address.
     */
    public ?string $email;
}
