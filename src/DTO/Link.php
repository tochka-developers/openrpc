<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\LinkReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * The Link object represents a possible design-time link for a result. The presence of a link does not guarantee
 * the caller’s ability to successfully invoke it, rather it provides a known relationship and traversal mechanism
 * between results and other methods.
 *
 * Unlike dynamic links (i.e. links provided in the result payload), the OpenRPC linking mechanism does not require
 * link information in the runtime result.
 *
 * For computing links, and providing instructions to execute them, a runtime expression is used for accessing
 * values in an method and using them as parameters while invoking the linked method.
 */
final class Link extends DataTransferObject implements LinkReferenceInterface
{
    /**
     * REQUIRED. Canonical name of the link.
     */
    public string $name;
    
    /**
     * A description of the link. GitHub Flavored Markdown syntax MAY be used for rich text representation.
     */
    public string $description;
    
    /**
     * Short description for the link.
     */
    public string $summary;
    
    /**
     * The name of an existing, resolvable OpenRPC method, as defined with a unique method.
     * This field MUST resolve to a unique Method Object. As opposed to Open Api, Relative method values
     * ARE NOT permitted.
     */
    public string $method;
    
    /**
     * A map representing parameters to pass to a method as specified with method. The key is the parameter name
     * to be used, whereas the value can be a constant or a runtime expression to be evaluated and passed to the
     * linked method.
     *
     * @var array<string, mixed>
     */
    public array $params;
    
    /**
     * A server object to be used by the target method.
     */
    public Server $server;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getLink(): Link
    {
        return $this;
    }
}
