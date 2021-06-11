<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ExampleReferenceInterface;
use Tochka\OpenRpc\DataTransferObject;

/**
 * The Example object is an object the defines an example that is intended to match a given Content Descriptor Schema.
 * If the Content Descriptor Schema includes examples, the value from this Example Object supercedes the value
 * of the schema example.
 */
class Example extends DataTransferObject implements ExampleReferenceInterface
{
    /**
     * Canonical name of the example.
     */
    public ?string $name = null;
    
    /**
     * Short description for the example.
     */
    public ?string $summary = null;
    
    /**
     * A verbose explanation of the example. GitHub Flavored Markdown syntax MAY be used for rich text representation.
     */
    public ?string $description = null;
    
    /**
     * Embedded literal example. The value field and externalValue field are mutually exclusive. To represent examples
     * of media types that cannot naturally represented in JSON, use a string value to contain the example,
     * escaping where necessary.
     *
     * @var mixed
     */
    public $value;
    
    /**
     * A URL that points to the literal example. This provides the capability to reference examples that
     * cannot easily be included in JSON documents. The value field and externalValue field are mutually exclusive.
     */
    public ?string $externalValue = null;
    
    public function getExample(): ?Example
    {
        return $this;
    }
}
