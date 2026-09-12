<?php

namespace App\Contracts;

use App\Models\Referral;
use App\Models\User;

interface ReferralService
{
    public function attach(User $referred, string $code): Referral;
    public function qualify(Referral $referral): Referral;
}
