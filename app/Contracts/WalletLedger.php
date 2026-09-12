<?php

namespace App\Contracts;

use App\DTOs\WalletReconciliationResult;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;

interface WalletLedger
{
    public function transactions(User $user, string $currency = 'IRR', int $limit = 50): Collection;

    public function reconcile(User $user, string $currency = 'IRR'): WalletReconciliationResult;

    public function verifyIntegrity(Wallet $wallet): WalletReconciliationResult;
}
