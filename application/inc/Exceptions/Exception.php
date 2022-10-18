<?php

namespace App\Exceptions;

use Exception as NativeException;
use Throwable;

class Exception extends NativeException
{
    /**
     * Set up the exception.
     *
     * @param ?Throwable $previous
     */
    public function __construct(string $message, int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
