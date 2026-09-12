<?php

namespace App\Contracts;

use App\Models\DiscountCode;
use App\Models\User;

interface DiscountService
{
    public function validate(string $code, User $user, int $subtotal): DiscountCode;
    public function calculate(DiscountCode $discount, int $subtotal): int;
    public function consume(DiscountCode $discount, User $user, int $orderId, int $amount): void;
}
