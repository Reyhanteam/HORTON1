<?php

namespace App\Contracts;

interface AdminAuthenticator
{
    public function attempt(string $email, string $password, bool $remember = false): bool;

    public function logout(): void;

    public function authenticated(): bool;
}
