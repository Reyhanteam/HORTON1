<?php

namespace App\Services\Marketing;

use App\Contracts\DiscountService;
use App\Enums\DiscountType;
use App\Exceptions\DomainRuleViolation;
use App\Models\DiscountCode;
use App\Models\DiscountUsage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

abstract class AbstractDiscountService implements DiscountService
{
    final public function validate(string $code, User $user, int $subtotal): DiscountCode
    {
        if ($subtotal < 0) throw new DomainRuleViolation('Subtotal cannot be negative.', 'discount.subtotal');
        $discount = DiscountCode::query()->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->lockForUpdate()->first();
        if (!$discount || !$discount->is_active) throw new DomainRuleViolation('Discount code is invalid or inactive.', 'discount.invalid');
        $now = Carbon::now();
        if (($discount->starts_at && $now->lt($discount->starts_at)) || ($discount->expires_at && $now->gt($discount->expires_at))) throw new DomainRuleViolation('Discount code is outside its validity period.', 'discount.expired');
        if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) throw new DomainRuleViolation('Discount usage limit reached.', 'discount.usage_limit');
        if ($discount->usage_limit_per_user !== null && DiscountUsage::query()->where('discount_code_id',$discount->id)->where('user_id',$user->id)->count() >= $discount->usage_limit_per_user) throw new DomainRuleViolation('Your usage limit for this discount is reached.', 'discount.user_limit');
        if ($subtotal < $discount->minimum_order_amount) throw new DomainRuleViolation('Order minimum for this discount is not met.', 'discount.minimum_order');
        return $discount;
    }

    final public function calculate(DiscountCode $discount, int $subtotal): int
    {
        if ($subtotal <= 0) return 0;
        $amount = match ($discount->type) {
            DiscountType::PERCENTAGE => intdiv($subtotal * (int) $discount->value, 100),
            DiscountType::FIXED => min($subtotal, (int) $discount->value),
        };
        return $discount->maximum_discount_amount !== null ? min($amount, (int) $discount->maximum_discount_amount) : $amount;
    }

    final public function consume(DiscountCode $discount, User $user, int $orderId, int $amount): void
    {
        if ($amount <= 0) return;
        DB::transaction(function () use ($discount, $user, $orderId, $amount) {
            if (DiscountUsage::query()->where('discount_code_id',$discount->id)->where('order_id',$orderId)->exists()) return;
            DiscountUsage::query()->create(['discount_code_id'=>$discount->id,'user_id'=>$user->id,'order_id'=>$orderId,'amount'=>$amount]);
            $discount->increment('used_count');
        });
    }
}
