<?php

namespace App\Services\Catalog;

use App\Contracts\PricingService;
use App\DTOs\PriceQuote;
use App\Exceptions\DomainRuleViolation;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Support\Carbon;

abstract class AbstractPricingService implements PricingService
{
    final public function price(Plan $plan, string $currency = 'IRR'): int
    {
        return $this->quote($plan, $currency)->amount;
    }

    final public function quote(Plan $plan, string $currency = 'IRR'): PriceQuote
    {
        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new DomainRuleViolation('Currency must be a valid 3-letter code.', 'pricing.currency');
        }

        $now = Carbon::now();
        $price = $plan->prices()
            ->where('currency', $currency)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('is_default')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();

        if ($price instanceof PlanPrice) {
            $this->validatePrice($price);

            return new PriceQuote(
                planId: $plan->id,
                currency: $currency,
                amount: (int) $price->amount,
                source: 'plan_price',
                validFrom: $price->starts_at,
                validUntil: $price->ends_at,
            );
        }

        if (strtoupper((string) $plan->currency) !== $currency) {
            throw new DomainRuleViolation('No price is configured for the requested currency.', 'pricing.currency_unavailable');
        }

        if ((int) $plan->price < 0) {
            throw new DomainRuleViolation('Plan price cannot be negative.', 'pricing.amount');
        }

        return new PriceQuote(
            planId: $plan->id,
            currency: $currency,
            amount: (int) $plan->price,
            source: 'plan',
        );
    }

    private function validatePrice(PlanPrice $price): void
    {
        if ((int) $price->amount < 0) {
            throw new DomainRuleViolation('Plan price cannot be negative.', 'pricing.amount');
        }

        if ($price->starts_at && $price->ends_at && $price->starts_at->greaterThan($price->ends_at)) {
            throw new DomainRuleViolation('Price validity window is invalid.', 'pricing.validity');
        }
    }
}
