<?php

namespace App\Exceptions;

use RuntimeException;

class DomainRuleViolation extends RuntimeException
{
    public function __construct(string $message, public readonly string $rule = 'domain_rule')
    {
        parent::__construct($message);
    }
}
