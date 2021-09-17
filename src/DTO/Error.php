<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\ErrorReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

/**
 * Defines an application level error.
 */
final class Error extends DataTransferObject implements ErrorReferenceInterface
{
    /**
     * REQUIRED. A Number that indicates the error type that occurred. This MUST be an integer. The error codes
     * from and including -32768 to -32000 are reserved for pre-defined errors. These pre-defined errors
     * SHOULD be assumed to be returned from any JSON-RPC api.
     */
    public int $code;
    
    /**
     * REQUIRED. A String providing a short description of the error. The message SHOULD be limited to a
     * concise single sentence.
     */
    public string $message;
    
    /**
     * A Primitive or Structured value that contains additional information about the error. This may be omitted.
     * The value of this member is defined by the Server (e.g. detailed error information, nested errors etc.).
     * @var mixed
     */
    public $data;
    
    public function __construct(int $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
        
        unset($this->data);
    }
    
    public function getError(): Error
    {
        return $this;
    }
}
