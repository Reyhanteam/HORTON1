<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Middleware\RequireAdminPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('/dashboard', 'admin.dashboard')->name('admin.dashboard');

    Route::get('/users', [UserController::class, 'index'])->middleware(RequireAdminPermission::class.':users.view')->name('admin.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware(RequireAdminPermission::class.':users.view')->name('admin.users.show');
    Route::patch('/users/{user}/status', [UserController::class, 'status'])->middleware(RequireAdminPermission::class.':users.manage')->name('admin.users.status');
    Route::get('/users/{user}/telegram', [UserController::class, 'telegram'])->middleware(RequireAdminPermission::class.':users.view')->name('admin.users.telegram');

    Route::get('/users/{user}/wallet', [WalletController::class, 'show'])->middleware(RequireAdminPermission::class.':wallet.view')->name('admin.wallet.show');
    Route::get('/users/{user}/wallet/transactions', [WalletController::class, 'transactions'])->middleware(RequireAdminPermission::class.':wallet.view')->name('admin.wallet.transactions');
    Route::post('/users/{user}/wallet/credit', [WalletController::class, 'credit'])->middleware(RequireAdminPermission::class.':wallet.credit')->name('admin.wallet.credit');
    Route::post('/users/{user}/wallet/debit', [WalletController::class, 'debit'])->middleware(RequireAdminPermission::class.':wallet.debit')->name('admin.wallet.debit');
    Route::patch('/users/{user}/wallet/status', [WalletController::class, 'status'])->middleware(RequireAdminPermission::class.':wallet.manage')->name('admin.wallet.status');

    Route::get('/notifications', [NotificationController::class, 'index'])->middleware(RequireAdminPermission::class.':notifications.view')->name('admin.notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->middleware(RequireAdminPermission::class.':notifications.view')->name('admin.notifications.show');

    Route::get('/broadcasts', [BroadcastController::class, 'index'])->middleware(RequireAdminPermission::class.':broadcasts.view')->name('admin.broadcasts.index');
    Route::post('/broadcasts', [BroadcastController::class, 'store'])->middleware(RequireAdminPermission::class.':broadcasts.create')->name('admin.broadcasts.store');
    Route::get('/broadcasts/{broadcast}', [BroadcastController::class, 'show'])->middleware(RequireAdminPermission::class.':broadcasts.view')->name('admin.broadcasts.show');
    Route::post('/broadcasts/{broadcast}/queue', [BroadcastController::class, 'queue'])->middleware(RequireAdminPermission::class.':broadcasts.send')->name('admin.broadcasts.queue');
    Route::post('/broadcasts/{broadcast}/cancel', [BroadcastController::class, 'cancel'])->middleware(RequireAdminPermission::class.':broadcasts.cancel')->name('admin.broadcasts.cancel');

    Route::get('/support/departments', [SupportController::class, 'departments'])->middleware(RequireAdminPermission::class.':support.departments.view')->name('admin.support.departments.index');
    Route::post('/support/departments', [SupportController::class, 'storeDepartment'])->middleware(RequireAdminPermission::class.':support.departments.manage')->name('admin.support.departments.store');
    Route::patch('/support/departments/{department}', [SupportController::class, 'updateDepartment'])->middleware(RequireAdminPermission::class.':support.departments.manage')->name('admin.support.departments.update');
    Route::delete('/support/departments/{department}', [SupportController::class, 'destroyDepartment'])->middleware(RequireAdminPermission::class.':support.departments.manage')->name('admin.support.departments.destroy');

    Route::get('/support/content', [SupportController::class, 'contents'])->middleware(RequireAdminPermission::class.':support.content.view')->name('admin.support.content.index');
    Route::post('/support/content', [SupportController::class, 'storeContent'])->middleware(RequireAdminPermission::class.':support.content.manage')->name('admin.support.content.store');
    Route::patch('/support/content/{content}', [SupportController::class, 'updateContent'])->middleware(RequireAdminPermission::class.':support.content.manage')->name('admin.support.content.update');
    Route::delete('/support/content/{content}', [SupportController::class, 'destroyContent'])->middleware(RequireAdminPermission::class.':support.content.manage')->name('admin.support.content.destroy');

    Route::get('/support/tickets', [SupportController::class, 'tickets'])->middleware(RequireAdminPermission::class.':support.tickets.view')->name('admin.support.tickets.index');
    Route::get('/support/tickets/{ticket}', [SupportController::class, 'showTicket'])->middleware(RequireAdminPermission::class.':support.tickets.view')->name('admin.support.tickets.show');
    Route::post('/support/tickets/{ticket}/reply', [SupportController::class, 'reply'])->middleware(RequireAdminPermission::class.':support.tickets.reply')->name('admin.support.tickets.reply');
    Route::patch('/support/tickets/{ticket}/status', [SupportController::class, 'status'])->middleware(RequireAdminPermission::class.':support.tickets.manage')->name('admin.support.tickets.status');
});
