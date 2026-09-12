<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\RequireAdminPermission;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware(AuthenticateAdmin::class)->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', fn () => response()->json(auth('admin')->user()))->name('me');
        Route::get('/users', fn () => response()->json(['message' => 'Users endpoint ready.']))
            ->middleware(RequireAdminPermission::class.':users.view')
            ->name('users.index');
    });
});
