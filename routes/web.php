<?php

declare(strict_types=1);

use App\Http\Controllers\UserPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function (): void {
    Route::prefix('shop')->name('portal.')->group(function (): void {
        Route::get('/', [UserPortalController::class, 'shop'])->name('shop');
        Route::get('/category/{category}', [UserPortalController::class, 'shop'])->name('shop.category');
        Route::get('/products/{product}', [UserPortalController::class, 'product'])->name('products.show');
        Route::get('/products/{product}/checkout', [UserPortalController::class, 'checkout'])->name('checkout');
    });

    Route::prefix('orders')->name('portal.orders')->group(function (): void {
        Route::get('/', [UserPortalController::class, 'orders'])->name('');
        Route::get('/{order}', [UserPortalController::class, 'order'])->name('.show');
    });

    Route::prefix('services')->name('portal.services')->group(function (): void {
        Route::get('/', [UserPortalController::class, 'services'])->name('');
        Route::get('/{service}', [UserPortalController::class, 'service'])->name('.show');
    });

    Route::prefix('wallet')->name('portal.wallet')->group(function (): void {
        Route::get('/', [UserPortalController::class, 'wallet'])->name('');
        Route::get('/top-up', [UserPortalController::class, 'topUp'])->name('.topup');
        Route::get('/transactions', [UserPortalController::class, 'transactions'])->name('.transactions');
    });

    Route::get('/referrals', [UserPortalController::class, 'referrals'])->name('portal.referrals');
    Route::get('/notifications', [UserPortalController::class, 'notifications'])->name('portal.notifications');
    Route::get('/discounts', [UserPortalController::class, 'discounts'])->name('portal.discounts');

    Route::prefix('support')->name('portal.support')->group(function (): void {
        Route::get('/', [UserPortalController::class, 'support'])->name('');
        Route::get('/new', [UserPortalController::class, 'createTicket'])->name('.create');
        Route::get('/tickets/{ticket}', [UserPortalController::class, 'ticket'])->name('.show');
        Route::get('/faq', [UserPortalController::class, 'faq'])->name('.faq');
        Route::get('/tutorials', [UserPortalController::class, 'tutorials'])->name('.tutorials');
    });

    Route::get('/profile', [UserPortalController::class, 'profile'])->name('portal.profile');
});
