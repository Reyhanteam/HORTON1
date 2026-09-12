<?php

namespace App\Services\Wallet;

use App\Contracts\WalletService;
use App\DTOs\WalletMutationData;
use App\Enums\TransactionDirection;
use App\Exceptions\DomainRuleViolation;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

abstract class AbstractWalletService implements WalletService
{
    final public function getOrCreate(User $user, string $currency = 'IRR'): Wallet
    {
        return Wallet::query()->firstOrCreate(['user_id' => $user->id, 'currency' => strtoupper($currency)], ['balance' => 0, 'status' => 'active']);
    }

    final public function credit(User $user, WalletMutationData $data, string $currency = 'IRR'): WalletTransaction
    {
        return $this->mutate($user, $data, TransactionDirection::CREDIT, $currency);
    }

    final public function debit(User $user, WalletMutationData $data, string $currency = 'IRR'): WalletTransaction
    {
        return $this->mutate($user, $data, TransactionDirection::DEBIT, $currency);
    }

    private function mutate(User $user, WalletMutationData $data, TransactionDirection $direction, string $currency): WalletTransaction
    {
        if ($data->amount <= 0) throw new DomainRuleViolation('Wallet amount must be positive.', 'wallet.amount');
        return DB::transaction(function () use ($user, $data, $direction, $currency) {
            $wallet = Wallet::query()->where('user_id', $user->id)->where('currency', strtoupper($currency))->lockForUpdate()->first();
            if (!$wallet) $wallet = Wallet::query()->create(['user_id'=>$user->id,'currency'=>strtoupper($currency),'balance'=>0,'status'=>'active']);
            if ($wallet->status !== 'active') throw new DomainRuleViolation('Wallet is not active.', 'wallet.status');
            if ($data->idempotencyKey) {
                $existing = WalletTransaction::query()->where('idempotency_key', $data->idempotencyKey)->first();
                if ($existing) return $existing;
            }
            $before = (int) $wallet->balance;
            $after = $direction === TransactionDirection::CREDIT ? $before + $data->amount : $before - $data->amount;
            if ($after < 0) throw new DomainRuleViolation('Insufficient wallet balance.', 'wallet.insufficient_balance');
            $wallet->update(['balance'=>$after]);
            return WalletTransaction::query()->create(['wallet_id'=>$wallet->id,'user_id'=>$user->id,'type'=>$data->type,'direction'=>$direction,'amount'=>$data->amount,'balance_before'=>$before,'balance_after'=>$after,'reference_type'=>$data->referenceType,'reference_id'=>$data->referenceId,'description'=>$data->description,'idempotency_key'=>$data->idempotencyKey,'metadata'=>$data->metadata]);
        });
    }
}
