<?php

namespace App\Services\Marketing;

use App\Contracts\GiftCodeService;
use App\Exceptions\DomainRuleViolation;
use App\Models\GiftCode;
use App\Models\GiftCodeRedemption;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

abstract class AbstractGiftCodeService implements GiftCodeService
{
    final public function validate(string $code, User $user): GiftCode
    {
        $gift = GiftCode::query()->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->where('is_active',true)->first();
        if (!$gift) throw new DomainRuleViolation('Gift code is invalid or inactive.', 'gift.invalid');
        $now=Carbon::now();
        if (($gift->starts_at && $now->lt($gift->starts_at)) || ($gift->expires_at && $now->gt($gift->expires_at))) throw new DomainRuleViolation('Gift code is outside its validity period.', 'gift.expired');
        if ($gift->usage_limit !== null && $gift->used_count >= $gift->usage_limit) throw new DomainRuleViolation('Gift code usage limit reached.', 'gift.usage_limit');
        if (GiftCodeRedemption::query()->where('gift_code_id',$gift->id)->where('user_id',$user->id)->exists()) throw new DomainRuleViolation('Gift code already redeemed by this user.', 'gift.already_redeemed');
        return $gift;
    }

    final public function redeem(GiftCode $gift, User $user, ?int $orderId = null): GiftCode
    {
        return DB::transaction(function () use ($gift,$user,$orderId) {
            if (GiftCodeRedemption::query()->where('gift_code_id',$gift->id)->where('user_id',$user->id)->exists()) throw new DomainRuleViolation('Gift code already redeemed.', 'gift.already_redeemed');
            GiftCodeRedemption::query()->create(['gift_code_id'=>$gift->id,'user_id'=>$user->id,'order_id'=>$orderId,'value'=>$gift->value]);
            $gift->increment('used_count');
            return $gift->refresh();
        });
    }
}
