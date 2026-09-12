<?php

namespace App\Services\Wallet;

use App\Contracts\WalletService;
use App\DTOs\WalletMutationData;
use App\Enums\TransactionDirection;
use App\Exceptions\DomainRuleViolation;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

abstract class AbstractWalletService implements WalletService
{
    final public function getOrCreate(User $user, string $currency = 'IRR'): Wallet
    {
        $currency = $this->normalizeCurrency($currency);
        try {
            Wallet::query()->firstOrCreate(['user_id' => $user->id, 'currency' => $currency], ['balance' => 0, 'status' => 'active']);
        } catch (QueryException $exception) {
            if (!Wallet::query()->where('user_id', $user->id)->where('currency', $currency)->exists()) throw $exception;
        }
        return Wallet::query()->where('user_id', $user->id)->where('currency', $currency)->firstOrFail();
    }

    final public function balance(User $user, string $currency = 'IRR'): int
    {
        return (int) $this->getOrCreate($user, $currency)->balance;
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
        $currency = $this->normalizeCurrency($currency);
        $this->getOrCreate($user, $currency);

        return DB::transaction(function () use ($user, $data, $direction, $currency): WalletTransaction {
            $wallet = Wallet::query()->where('user_id', $user->id)->where('currency', $currency)->lockForUpdate()->firstOrFail();
            if ($wallet->status !== 'active') throw new DomainRuleViolation('Wallet is not active.', 'wallet.status');

            if ($data->idempotencyKey) {
                $existing = WalletTransaction::query()->where('idempotency_key', $data->idempotencyKey)->first();
                if ($existing) {
                    $sameMutation = $existing->wallet_id === $wallet->id && $existing->user_id === $user->id && $existing->direction === $direction && (int) $existing->amount === $data->amount && $existing->type === $data->type;
                    if (!$sameMutation) throw new DomainRuleViolation('Wallet idempotency key is already used for another mutation.', 'wallet.idempotency_conflict');
                    return $existing;
                }
            }

            $before = (int) $wallet->balance;
            $after = $direction === TransactionDirection::CREDIT ? $before + $data->amount : $before - $data->amount;
            if ($after < 0) throw new DomainRuleViolation('Insufficient wallet balance.', 'wallet.insufficient_balance');

            $previous = WalletTransaction::query()->where('wallet_id', $wallet->id)->latest('id')->first();
            $now = now()->startOfSecond();
            $transaction = new WalletTransaction([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => $data->type,
                'direction' => $direction,
                'amount' => $data->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId,
                'description' => $data->description,
                'idempotency_key' => $data->idempotencyKey,
                'metadata' => $data->metadata,
                'created_at' => $now,
                'previous_hash' => $previous?->ledger_hash,
            ]);
            $transaction->ledger_hash = $transaction->calculateLedgerHash($transaction->previous_hash);

            $wallet->update(['balance' => $after]);
            $transaction->save();
            return $transaction;
        });
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) throw new DomainRuleViolation('Wallet currency must be a valid 3-letter ISO-style code.', 'wallet.currency');
        return $currency;
    }
}
