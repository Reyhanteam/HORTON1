<?php

namespace App\Actions\Wallet;

use App\Contracts\WalletService;
use App\DTOs\WalletMutationData;
use App\Models\User;
use App\Models\WalletTransaction;

final class CreditWalletAction
{
    public function __construct(private readonly WalletService $wallets) {}
    public function execute(User $user, WalletMutationData $data): WalletTransaction { return $this->wallets->credit($user, $data); }
}
