<?php

namespace App\Contracts;

use App\Models\CashbackTransaction;
use App\Models\User;

interface CashbackService
{
    public function earn(User $user, int $amount, string $type = 'cashback', ?string $referenceType = null, ?int $referenceId = null): CashbackTransaction;
    public function redeem(User $user, int $amount, string $type = 'redemption', ?string $referenceType = null, ?int $referenceId = null): CashbackTransaction;
}
