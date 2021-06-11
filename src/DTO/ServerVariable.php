<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\DataTransferObject;

/**
 * An object representing a Server Variable for server URL template substitution.
 */
class ServerVariable extends DataTransferObject
{
    /**
     * An enumeration of string values to be used if the substitution options are from a limited set.
     * @var array<string>
     */
    public array $enum;
    
    /**
     * REQUIRED. The default value to use for substitution, which SHALL be sent if an alternate value is not
     * supplied. Note this behavior is different than the Schema Object’s treatment of default values, because in
     * those cases parameter values are optional.
     */
    public string $default;
    
    /**
     * An optional description for the server variable. GitHub Flavored Markdown syntax MAY be used for rich
     * text representation.
     */
    public string $description;
    
    public function __construct(string $default)
    {
        $this->default = $default;
    }
}
