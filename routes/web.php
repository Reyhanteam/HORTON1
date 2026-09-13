<?php

declare(strict_types=1);

use App\Http\Controllers\UserPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function (): void {
    Route::get('/dashboard', [UserPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/shop', [UserPortalController::class, 'shop'])->name('portal.shop');
    Route::get('/shop/category/{category}', [UserPortalController::class, 'shop'])->name('portal.shop.category');
    Route::get('/shop/products/{product}', [UserPortalController::class, 'product'])->name('portal.products.show');
    Route::get('/orders', [UserPortalController::class, 'orders'])->name('portal.orders');
    Route::get('/orders/{order}', [UserPortalController::class, 'order'])->name('portal.orders.show');
    Route::get('/services', [UserPortalController::class, 'services'])->name('portal.services');
    Route::get('/services/{service}', [UserPortalController::class, 'service'])->name('portal.services.show');
    Route::get('/wallet', [UserPortalController::class, 'wallet'])->name('portal.wallet');
    Route::get('/referrals', [UserPortalController::class, 'referrals'])->name('portal.referrals');
    Route::get('/notifications', fn () => view('portal.alerts', ['notifications' => auth()->user()->notifications()->latest()->paginate(20)]))->name('portal.notifications');
    Route::get('/support', [UserPortalController::class, 'support'])->name('portal.support');
    Route::get('/support/tickets/{ticket}', [UserPortalController::class, 'ticket'])->name('portal.support.show');
    Route::get('/profile', [UserPortalController::class, 'profile'])->name('portal.profile');
});
