<?php

namespace App\Services\Wallet;

use App\Contracts\WalletLedger;
use App\DTOs\WalletReconciliationResult;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class AbstractWalletLedger implements WalletLedger
{
    final public function transactions(User $user, string $currency = 'IRR', int $limit = 50): Collection
    {
        if ($limit < 1 || $limit > 500) {
            throw new \InvalidArgumentException('Wallet ledger limit must be between 1 and 500.');
        }

        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('currency', strtoupper($currency))
            ->first();

        if (!$wallet) {
            return collect();
        }

        return $this->loadTransactions($wallet, $limit);
    }

    final public function reconcile(User $user, string $currency = 'IRR'): WalletReconciliationResult
    {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('currency', strtoupper($currency))
            ->first();

        if (!$wallet) {
            return new WalletReconciliationResult(true, 0, 0, 0);
        }

        return $this->verifyIntegrity($wallet);
    }

    final public function verifyIntegrity(Wallet $wallet): WalletReconciliationResult
    {
        return DB::transaction(function () use ($wallet): WalletReconciliationResult {
            $lockedWallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $transactions = WalletTransaction::query()
                ->where('wallet_id', $lockedWallet->id)
                ->orderBy('id')
                ->get();

            $calculated = 0;
            $previousHash = null;
            foreach ($transactions as $transaction) {
                if ((int) $transaction->balance_before !== $calculated) {
                    return new WalletReconciliationResult(false, (int) $lockedWallet->balance, $calculated, $transactions->count(), $transaction->id, 'balance_chain');
                }

                $expectedHash = $transaction->calculateLedgerHash($previousHash);
                if ($transaction->previous_hash !== $previousHash || !hash_equals((string) $transaction->ledger_hash, $expectedHash)) {
                    return new WalletReconciliationResult(false, (int) $lockedWallet->balance, $calculated, $transactions->count(), $transaction->id, 'ledger_hash');
                }

                $calculated = $transaction->balance_after;
                $previousHash = $transaction->ledger_hash;
            }

            $consistent = $calculated === (int) $lockedWallet->balance;

            return new WalletReconciliationResult(
                $consistent,
                (int) $lockedWallet->balance,
                $calculated,
                $transactions->count(),
                $consistent ? null : ($transactions->last()?->id),
                $consistent ? null : 'wallet_balance',
            );
        });
    }

    abstract protected function loadTransactions(Wallet $wallet, int $limit): Collection;
}
