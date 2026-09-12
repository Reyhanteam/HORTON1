<?php

namespace App\Contracts;

use App\DTOs\CheckoutData;
use App\DTOs\CheckoutResult;
use App\Models\User;

interface CheckoutService
{
    public function checkout(User $user, CheckoutData $data): CheckoutResult;
}
