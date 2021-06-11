<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\DataTransferObject;

/**
 * Holds a set of reusable objects for different aspects of the OpenRPC. All objects defined within the components
 * object will have no effect on the API unless they are explicitly referenced from properties outside the
 * components object.
 */
class Components extends DataTransferObject
{
    /**
     * An object to hold reusable Content Descriptor Objects
     * @var array<string, ContentDescriptor>
     */
    public array $contentDescriptors;
    
    /**
     * An object to hold reusable Schema Objects.
     * @var array<string, Schema>
     */
    public array $schemas;
    
    /**
     * An object to hold reusable Example Objects.
     * @var array<string, Example>
     */
    public array $examples;
    
    /**
     * An object to hold reusable Link Objects.
     * @var array<string, Link>
     */
    public array $links;
    
    /**
     * An object to hold reusable Error Objects.
     * @var array<string, Error>
     */
    public array $errors;
    
    /**
     * An object to hold reusable Example Pairing Objects.
     * @var array<string, ExamplePairing>
     */
    public array $examplePairingObjects;
    
    /**
     * An object to hold reusable Tag Objects.
     * @var array<string, Tag>
     */
    public array $tags;
}
