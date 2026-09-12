<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->string('previous_hash', 64)->nullable()->after('idempotency_key');
            $table->string('ledger_hash', 64)->nullable()->after('previous_hash');
            $table->index(['wallet_id', 'ledger_hash']);
        });

        DB::table('wallets')->orderBy('id')->each(function (object $wallet): void {
            $previousHash = null;
            DB::table('wallet_transactions')
                ->where('wallet_id', $wallet->id)
                ->orderBy('id')
                ->get()
                ->each(function (object $transaction) use (&$previousHash): void {
                    $payload = [
                        'wallet_id' => (int) $transaction->wallet_id,
                        'user_id' => (int) $transaction->user_id,
                        'type' => $transaction->type,
                        'direction' => $transaction->direction,
                        'amount' => (int) $transaction->amount,
                        'balance_before' => (int) $transaction->balance_before,
                        'balance_after' => (int) $transaction->balance_after,
                        'reference_type' => $transaction->reference_type,
                        'reference_id' => $transaction->reference_id,
                        'description' => $transaction->description,
                        'idempotency_key' => $transaction->idempotency_key,
                        'metadata' => $transaction->metadata ? json_decode($transaction->metadata, true, 512, JSON_THROW_ON_ERROR) : [],
                        'created_at' => $transaction->created_at,
                        'previous_hash' => $previousHash,
                    ];

                    $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                    DB::table('wallet_transactions')->where('id', $transaction->id)->update([
                        'previous_hash' => $previousHash,
                        'ledger_hash' => $hash,
                    ]);
                    $previousHash = $hash;
                });
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropIndex(['wallet_id', 'ledger_hash']);
            $table->dropColumn(['previous_hash', 'ledger_hash']);
        });
    }
};
