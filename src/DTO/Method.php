<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ContentDescriptorReferenceInterface;
use Tochka\OpenRpc\Contracts\ErrorReferenceInterface;
use Tochka\OpenRpc\Contracts\ExamplePairingReferenceInterface;
use Tochka\OpenRpc\Contracts\LinkReferenceInterface;
use Tochka\OpenRpc\Contracts\TagReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * Describes the interface for the given method name. The method name is used as the method field of the JSON-RPC body.
 * It therefore MUST be unique.
 */
final class Method extends DataTransferObject
{
    /**
     * REQUIRED. The canonical name for the method. The name MUST be unique within the methods array.
     */
    public string $name;
    
    /**
     * A list of tags for API documentation control. Tags can be used for logical grouping of methods by
     * resources or any other qualifier.
     *
     * @var array<TagReferenceInterface>
     */
    public ?array $tags;
    
    /**
     * A short summary of what the method does.
     */
    public ?string $summary;
    
    /**
     * A verbose explanation of the method behavior. GitHub Flavored Markdown syntax MAY be used for
     * rich text representation.
     */
    public ?string $description;
    
    /**
     * Additional external documentation for this method.
     */
    public ?ExternalDocumentation $externalDocs;
    
    /**
     * REQUIRED. A list of parameters that are applicable for this method. The list MUST NOT include duplicated
     * parameters and therefore require name to be unique. The list can use the Reference Object to link to
     * parameters that are defined by the Content Descriptor Object. All optional params (content descriptor
     * objects with “required”: false) MUST be positioned after all required params in the list.
     *
     * @var array<ContentDescriptorReferenceInterface>
     */
    public array $params;
    
    /**
     * REQUIRED. The description of the result returned by the method. It MUST be a Content Descriptor.
     *
     * @var ContentDescriptorReferenceInterface
     */
    public ContentDescriptorReferenceInterface $result;
    
    /**
     * Declares this method to be deprecated. Consumers SHOULD refrain from usage of the declared method.
     * Default value is false.
     */
    public bool $deprecated;
    
    /**
     * An alternative servers array to service this method. If an alternative servers array is specified at the
     * Root level, it will be overridden by this value.
     *
     * @var array<Server>
     */
    public array $servers = [];
    
    /**
     * A list of custom application defined errors that MAY be returned. The Errors MUST have unique error codes.
     * @var array<ErrorReferenceInterface>|null
     */
    public ?array $errors;
    
    /**
     * A list of possible links from this method call.
     * @var array<LinkReferenceInterface>|null
     */
    public ?array $links;
    
    /**
     * The expected format of the parameters. As per the JSON-RPC 2.0 specification, the params of a JSON-RPC
     * request object may be an array, object, or either (represented as by-position, by-name, and either respectively).
     * When a method has a paramStructure value of by-name, callers of the method MUST send a JSON-RPC request object
     * whose params field is an object. Further, the key names of the params object MUST be the same as the
     * contentDescriptor.names for the given method. Defaults to "either".
     *
     * @var string|null
     */
    public string $paramStructure = 'either';
    
    /**
     * Array of Example Pairing Object where each example includes a valid params-to-result Content Descriptor pairing.
     *
     * @var array<ExamplePairingReferenceInterface>|null
     */
    public ?array $examples;
    
    /**
     * @param string $name
     * @param array<ContentDescriptorReferenceInterface> $params
     * @param ContentDescriptorReferenceInterface $result
     */
    public function __construct(string $name, array $params, ContentDescriptorReferenceInterface $result)
    {
        $this->name = $name;
        $this->params = $params;
        $this->result = $result;
    }
}
