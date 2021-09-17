<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ExamplePairingReferenceInterface;
use Tochka\OpenRpc\Contracts\ExampleReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

final class ExamplePairing extends DataTransferObject implements ExamplePairingReferenceInterface
{
    /**
     * Name for the example pairing.
     */
    public ?string $name;
    
    /**
     * A verbose explanation of the example pairing.
     */
    public ?string $description;
    
    /**
     * Short description for the example pairing.
     */
    public ?string $summary;
    
    /**
     * Example parameters.
     *
     * @var array<ExampleReferenceInterface>
     */
    public array $params;
    
    /**
     * Example result.
     */
    public ?ExampleReferenceInterface $result;
    
    public function getExamplePairing(): ExamplePairing
    {
        return $this;
    }
}
