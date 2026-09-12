<?php

namespace App\Contracts;

use App\Models\GiftCode;
use App\Models\User;

interface GiftCodeService
{
    public function validate(string $code, User $user): GiftCode;
    public function redeem(GiftCode $gift, User $user, ?int $orderId = null): GiftCode;
}
