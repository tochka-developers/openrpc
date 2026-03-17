<?php

namespace Tochka\OpenRpc\DTO;

final class OpenRpc
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
     * @var array<MethodDescriptor>
     */
    public array $methods = [];
    
    /**
     * An element to hold various schemas for the specification.
     */
    public ?Components $components;
    
    /**
     * OpenRpc constructor.
     * @param string $version
     * @param Info $info
     */
    public function __construct(string $version, Info $info)
    {
        $this->openrpc = $version;
        $this->info = $info;
    }
}
