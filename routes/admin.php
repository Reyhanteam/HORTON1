<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\NotificationController;
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

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware(RequireAdminPermission::class.':notifications.view')
            ->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])
            ->middleware(RequireAdminPermission::class.':notifications.view')
            ->name('notifications.show');

        Route::get('/broadcasts', [BroadcastController::class, 'index'])
            ->middleware(RequireAdminPermission::class.':broadcasts.view')
            ->name('broadcasts.index');
        Route::post('/broadcasts', [BroadcastController::class, 'store'])
            ->middleware(RequireAdminPermission::class.':broadcasts.create')
            ->name('broadcasts.store');
        Route::get('/broadcasts/{broadcast}', [BroadcastController::class, 'show'])
            ->middleware(RequireAdminPermission::class.':broadcasts.view')
            ->name('broadcasts.show');
        Route::post('/broadcasts/{broadcast}/queue', [BroadcastController::class, 'queue'])
            ->middleware(RequireAdminPermission::class.':broadcasts.send')
            ->name('broadcasts.queue');
        Route::post('/broadcasts/{broadcast}/cancel', [BroadcastController::class, 'cancel'])
            ->middleware(RequireAdminPermission::class.':broadcasts.cancel')
            ->name('broadcasts.cancel');
    });
});
