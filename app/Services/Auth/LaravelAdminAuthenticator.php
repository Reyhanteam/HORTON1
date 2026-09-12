<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;

final class LaravelAdminAuthenticator extends AbstractAdminAuthenticator
{
    protected function attemptCredentials(string $email, string $password, bool $remember): bool
    {
        $credentials = [
            'email' => $email,
            'password' => $password,
            'status' => 'active',
        ];

        return Auth::guard('admin')->attempt($credentials, $remember);
    }
}
