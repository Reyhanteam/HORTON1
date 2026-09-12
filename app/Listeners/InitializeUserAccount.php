<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\UserProfile;
use App\Services\Wallet\DatabaseWalletService;

final class InitializeUserAccount
{
    public function __construct(private readonly DatabaseWalletService $wallets) {}

    public function handle(UserRegistered $event): void
    {
        UserProfile::query()->firstOrCreate(['user_id'=>$event->user->id], ['metadata'=>[]]);
        $this->wallets->getOrCreate($event->user);
    }
}
