<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\ExampleReferenceInterface;
use Tochka\OpenRpc\DTO\Example;

/**
 * @extends AbstractReference<Example>
 */
final class ExampleReference extends AbstractReference implements ExampleReferenceInterface
{
    protected function getPath(): string
    {
        return '#/components/examples';
    }
    
    public function getExample(): Example
    {
        return $this->getItem();
    }
}
