<?php

namespace Tochka\OpenRpc\DTO\References;

use Tochka\OpenRpc\Contracts\ErrorReferenceInterface;
use Tochka\OpenRpc\DTO\Error;

/**
 * @extends AbstractReference<Error>
 */
final class ErrorReference extends AbstractReference implements ErrorReferenceInterface
{
    protected function getPath(): string
    {
        return '#/components/errors';
    }
    
    public function getError(): Error
    {
        return $this->getItem();
    }
}
