<?php

namespace Tests\Feature;

use App\Contracts\WalletLedger;
use App\Contracts\WalletService;
use App\DTOs\WalletMutationData;
use App\Exceptions\DomainRuleViolation;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_is_created_once_per_user_and_currency_and_balance_is_available(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class);
        $first = $wallet->getOrCreate($user, 'irr'); $second = $wallet->getOrCreate($user, 'IRR');
        $this->assertTrue($first->is($second)); $this->assertSame(0, $wallet->balance($user, 'IRR')); $this->assertSame(1, Wallet::query()->where('user_id', $user->id)->count());
    }

    public function test_credit_and_debit_keep_an_immutable_balance_chain(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class);
        $credit = $wallet->credit($user, new WalletMutationData(100000, 'deposit', 'Initial credit', 'ledger-1'));
        $debit = $wallet->debit($user, new WalletMutationData(25000, 'purchase', 'Purchase', 'ledger-2'));
        $this->assertSame(0, $credit->balance_before); $this->assertSame(100000, $credit->balance_after); $this->assertSame(100000, $debit->balance_before); $this->assertSame(75000, $debit->balance_after);
        $this->assertNull($credit->previous_hash); $this->assertSame($credit->ledger_hash, $debit->previous_hash); $this->assertNotSame($credit->ledger_hash, $debit->ledger_hash); $this->assertSame(75000, $wallet->balance($user));
    }

    public function test_idempotency_returns_the_original_transaction_without_double_credit(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class);
        $first = $wallet->credit($user, new WalletMutationData(50000, 'deposit', idempotencyKey: 'same-key')); $same = $wallet->credit($user, new WalletMutationData(50000, 'deposit', idempotencyKey: 'same-key'));
        $this->assertTrue($first->is($same)); $this->assertSame(50000, $wallet->balance($user)); $this->assertSame(1, $user->wallets()->first()->transactions()->count());
    }

    public function test_reusing_an_idempotency_key_for_a_different_mutation_is_rejected(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class); $wallet->credit($user, new WalletMutationData(50000, 'deposit', idempotencyKey: 'conflict-key'));
        $this->expectException(DomainRuleViolation::class); $this->expectExceptionMessage('Wallet idempotency key is already used for another mutation.');
        $wallet->credit($user, new WalletMutationData(60000, 'deposit', idempotencyKey: 'conflict-key'));
    }

    public function test_insufficient_debit_does_not_change_wallet_or_ledger(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class); $wallet->credit($user, new WalletMutationData(10000, 'deposit', idempotencyKey: 'before-debit'));
        $this->expectException(DomainRuleViolation::class); $this->expectExceptionMessage('Insufficient wallet balance.');
        try { $wallet->debit($user, new WalletMutationData(10001, 'purchase', idempotencyKey: 'failed-debit')); } finally { $this->assertSame(10000, $wallet->balance($user)); $this->assertSame(1, $user->wallets()->first()->transactions()->count()); }
    }

    public function test_ledger_reconciliation_detects_balance_and_hash_tampering(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class); $ledger = $this->app->make(WalletLedger::class);
        $wallet->credit($user, new WalletMutationData(100000, 'deposit', idempotencyKey: 'integrity-1')); $wallet->debit($user, new WalletMutationData(10000, 'purchase', idempotencyKey: 'integrity-2'));
        $valid = $ledger->reconcile($user); $this->assertTrue($valid->consistent); $this->assertSame(90000, $valid->storedBalance); $this->assertSame(90000, $valid->calculatedBalance); $this->assertSame(2, $valid->transactionCount);
        DB::table('wallet_transactions')->where('id', 2)->update(['amount' => 99999]);
        $invalid = $ledger->reconcile($user); $this->assertFalse($invalid->consistent); $this->assertSame(2, $invalid->firstInvalidTransactionId);
    }

    public function test_ledger_transactions_are_readable_in_reverse_chronological_order(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class); $ledger = $this->app->make(WalletLedger::class);
        $wallet->credit($user, new WalletMutationData(1000, 'deposit', idempotencyKey: 'history-1')); $wallet->credit($user, new WalletMutationData(2000, 'deposit', idempotencyKey: 'history-2'));
        $items = $ledger->transactions($user, 'IRR', 50); $this->assertCount(2, $items); $this->assertSame(2000, $items->first()->amount); $this->assertSame(1000, $items->last()->amount);
    }

    public function test_wallet_transaction_cannot_be_updated_or_deleted_through_eloquent(): void
    {
        $user = User::factory()->create(); $transaction = $this->app->make(WalletService::class)->credit($user, new WalletMutationData(1000, 'deposit', idempotencyKey: 'immutable'));
        $this->expectException(LogicException::class); $transaction->update(['description' => 'tampered']);
    }

    public function test_wallet_currency_is_validated_and_normalized(): void
    {
        $user = User::factory()->create(); $wallet = $this->app->make(WalletService::class); $wallet->credit($user, new WalletMutationData(1000, 'deposit', idempotencyKey: 'eur-credit'), 'eur');
        $this->assertSame(1000, $wallet->balance($user, 'EUR')); $this->expectException(DomainRuleViolation::class); $wallet->balance($user, 'EURO');
    }
}
