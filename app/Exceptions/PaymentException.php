<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'payment.error', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
