<?php

namespace App\Exceptions;

use Throwable;

class InvalidInput extends Exception
{
    /**
     * Set up the exception.
     *
     * @param ?Throwable $previous
     */
    public function __construct(string $message, int $code = 422, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
