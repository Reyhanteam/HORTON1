<?php

namespace App\Services\Wallet;

use App\Models\Wallet;
use Illuminate\Support\Collection;

final class DatabaseWalletLedger extends AbstractWalletLedger
{
    protected function loadTransactions(Wallet $wallet, int $limit): Collection
    {
        return $wallet->transactions()
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
