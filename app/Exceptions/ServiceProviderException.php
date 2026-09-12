<?php

namespace App\Exceptions;

use RuntimeException;

class ServiceProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'provider.error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
