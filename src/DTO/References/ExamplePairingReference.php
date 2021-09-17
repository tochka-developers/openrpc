<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\ExamplePairingReferenceInterface;
use Tochka\OpenRpc\DTO\ExamplePairing;

/**
 * @extends AbstractReference<ExamplePairing>
 */
final class ExamplePairingReference extends AbstractReference implements ExamplePairingReferenceInterface
{
    protected function getPath(): string
    {
        return '#/components/examplePairingObjects';
    }
    
    public function getExamplePairing(): ExamplePairing
    {
        return $this->getItem();
    }
}
