<?php

namespace App\Services\Auth;

use App\Contracts\AdminAuthenticator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

abstract class AbstractAdminAuthenticator implements AdminAuthenticator
{
    final public function attempt(string $email, string $password, bool $remember = false): bool
    {
        if (blank($email) || blank($password)) {
            throw ValidationException::withMessages([
                'credentials' => 'Admin credentials are required.',
            ]);
        }

        if (! $this->attemptCredentials($email, $password, $remember)) {
            throw ValidationException::withMessages([
                'credentials' => 'The provided admin credentials are invalid.',
            ]);
        }

        return true;
    }

    final public function logout(): void
    {
        Auth::guard('admin')->logout();
    }

    final public function authenticated(): bool
    {
        return Auth::guard('admin')->check();
    }

    abstract protected function attemptCredentials(string $email, string $password, bool $remember): bool;
}
