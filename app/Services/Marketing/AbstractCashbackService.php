<?php

namespace App\Services\Marketing;

use App\Contracts\CashbackService;
use App\Enums\TransactionDirection;
use App\Exceptions\DomainRuleViolation;
use App\Models\CashbackAccount;
use App\Models\CashbackTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

abstract class AbstractCashbackService implements CashbackService
{
    final public function earn(User $user,int $amount,string $type='cashback',?string $referenceType=null,?int $referenceId=null): CashbackTransaction { return $this->mutate($user,$amount,$type,TransactionDirection::CREDIT,$referenceType,$referenceId); }
    final public function redeem(User $user,int $amount,string $type='redemption',?string $referenceType=null,?int $referenceId=null): CashbackTransaction { return $this->mutate($user,$amount,$type,TransactionDirection::DEBIT,$referenceType,$referenceId); }

    private function mutate(User $user,int $amount,string $type,TransactionDirection $direction,?string $referenceType,?int $referenceId): CashbackTransaction
    {
        if($amount<=0) throw new DomainRuleViolation('Cashback amount must be positive.','cashback.amount');
        return DB::transaction(function() use($user,$amount,$type,$direction,$referenceType,$referenceId){
            $account=CashbackAccount::query()->where('user_id',$user->id)->where('currency','IRR')->lockForUpdate()->first() ?? CashbackAccount::query()->create(['user_id'=>$user->id,'currency'=>'IRR','balance'=>0]);
            if($direction===TransactionDirection::DEBIT && $account->balance<$amount) throw new DomainRuleViolation('Insufficient cashback balance.','cashback.insufficient_balance');
            if($referenceType!==null && $referenceId!==null){
                $existing=CashbackTransaction::query()->where('reference_type',$referenceType)->where('reference_id',$referenceId)->where('type',$type)->first();
                if($existing) return $existing;
            }
            $account->balance=$direction===TransactionDirection::CREDIT?$account->balance+$amount:$account->balance-$amount; $account->save();
            return CashbackTransaction::query()->create(['cashback_account_id'=>$account->id,'user_id'=>$user->id,'type'=>$type,'direction'=>$direction,'amount'=>$amount,'reference_type'=>$referenceType,'reference_id'=>$referenceId]);
        });
    }
}
