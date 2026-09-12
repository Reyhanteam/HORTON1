<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AdminAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AuthController extends Controller
{
    public function login(Request $request, AdminAuthenticator $authenticator): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $authenticator->attempt(
            $credentials['email'],
            $credentials['password'],
            (bool) ($credentials['remember'] ?? false),
        );

        $request->session()->regenerate();

        return response()->json(['message' => 'Authenticated.']);
    }

    public function logout(Request $request, AdminAuthenticator $authenticator): JsonResponse
    {
        $authenticator->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
