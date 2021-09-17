<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ContentDescriptorReferenceInterface;
use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * Content Descriptors are objects that do just as they suggest - describe content.
 * They are reusable ways of describing either parameters or result. They MUST have a schema.
 */
final class ContentDescriptor extends DataTransferObject implements ContentDescriptorReferenceInterface
{
    protected array $onlyNotEmptyKeys = [
        'summary',
        'description',
    ];
    
    /**
     * REQUIRED. Name of the content that is being described. If the content described is a method parameter
     * assignable by-name, this field SHALL define the parameter’s key (ie name).
     */
    public string $name;
    
    /**
     * A short summary of the content that is being described.
     */
    public ?string $summary;
    
    /**
     * A verbose explanation of the content descriptor behavior. GitHub Flavored Markdown syntax MAY be
     * used for rich text representation.
     */
    public ?string $description;
    
    /**
     * Determines if the content is a required field. Default value is false.
     */
    public bool $required;
    
    /**
     * REQUIRED. Schema that describes the content.
     */
    public SchemaReferenceInterface $schema;
    
    /**
     * Specifies that the content is deprecated and SHOULD be transitioned out of usage. Default value is false.
     */
    public bool $deprecated;
    
    public function __construct(string $name, SchemaReferenceInterface $schema)
    {
        $this->name = $name;
        $this->schema = $schema;
    }
    
    public function getContentDescriptor(): ContentDescriptor
    {
        return $this;
    }
}
