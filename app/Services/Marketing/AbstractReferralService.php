<?php

namespace App\Services\Marketing;

use App\Contracts\ReferralService;
use App\Exceptions\DomainRuleViolation;
use App\Models\Referral;
use App\Models\ReferralAccount;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

abstract class AbstractReferralService implements ReferralService
{
    final public function attach(User $referred, string $code): Referral
    {
        $account = ReferralAccount::query()->where('code', trim($code))->where('status','active')->first();
        if (!$account) throw new DomainRuleViolation('Referral code is invalid.', 'referral.invalid');
        if ($account->user_id === $referred->id) throw new DomainRuleViolation('A user cannot refer themselves.', 'referral.self');
        if (Referral::query()->where('referred_user_id',$referred->id)->exists()) throw new DomainRuleViolation('User already has a referral.', 'referral.already_attached');
        return DB::transaction(fn () => Referral::query()->create(['referrer_user_id'=>$account->user_id,'referred_user_id'=>$referred->id,'source'=>'code','status'=>'registered','registered_at'=>Carbon::now()]));
    }

    final public function qualify(Referral $referral): Referral
    {
        if ($referral->status === 'qualified') return $referral;
        $referral->forceFill(['status'=>'qualified','qualified_at'=>Carbon::now()])->save();
        return $referral->refresh();
    }
}
