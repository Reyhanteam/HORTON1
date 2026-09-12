<?php

namespace App\Contracts;

use App\DTOs\WalletMutationData;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

interface WalletService
{
    public function getOrCreate(User $user, string $currency = 'IRR'): Wallet;
    public function balance(User $user, string $currency = 'IRR'): int;
    public function credit(User $user, WalletMutationData $data, string $currency = 'IRR'): WalletTransaction;
    public function debit(User $user, WalletMutationData $data, string $currency = 'IRR'): WalletTransaction;
}
