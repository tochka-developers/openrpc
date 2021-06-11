<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\DataTransferObject;

/**
 * This is the root object of the OpenRPC document. The contents of this object represent a whole OpenRPC
 * document. How this object is constructed or stored is outside the scope of the OpenRPC Specification.
 */
class OpenRpc extends DataTransferObject
{
    /**
     * REQUIRED. This string MUST be the semantic version number of the OpenRPC Specification version that the
     * OpenRPC document uses. The openrpc field SHOULD be used by tooling specifications and clients to interpret
     * the OpenRPC document. This is not related to the API info.version string.
     */
    public string $openrpc;
    
    /**
     * REQUIRED. Provides metadata about the API. The metadata MAY be used by tooling as required.
     */
    public Info $info;
    
    /**
     * An array of Server Objects, which provide connectivity information to a target server. If the servers
     * property is not provided, or is an empty array, the default value would be a Server Object with a url value
     * of localhost.
     *
     * @var array<Server>
     */
    public array $servers;
    
    /**
     * REQUIRED. The available methods for the API. While it is required, the array may be empty (to handle
     * security filtering, for example).
     *
     * @var array<Method>
     */
    public array $methods;
    
    /**
     * An element to hold various schemas for the specification.
     */
    public ?Components $components;
    
    /**
     * Additional external documentation.
     */
    public ?ExternalDocumentation $externalDocumentation;
    
    /**
     * OpenRpc constructor.
     * @param string $openrpc
     * @param Info $info
     * @param array<Method> $methods
     */
    public function __construct(string $openrpc, Info $info, array $methods)
    {
        $this->openrpc = $openrpc;
        $this->info = $info;
        $this->methods = $methods;
    }
}
