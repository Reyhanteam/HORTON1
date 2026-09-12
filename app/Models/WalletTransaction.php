<?php

namespace App\Models;

use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class WalletTransaction extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (WalletTransaction $transaction): void {
            if (blank($transaction->ledger_hash)) throw new LogicException('Wallet ledger transactions must be created through WalletService.');
        });
        static::updating(function (): never { throw new LogicException('Wallet ledger transactions are immutable.'); });
        static::deleting(function (): never { throw new LogicException('Wallet ledger transactions cannot be deleted.'); });
    }

    protected function casts(): array
    {
        return ['direction' => TransactionDirection::class, 'amount' => 'integer', 'balance_before' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime', 'metadata' => 'array'];
    }

    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function calculateLedgerHash(?string $previousHash): string
    {
        $payload = [
            'wallet_id' => $this->wallet_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'direction' => $this->direction instanceof TransactionDirection ? $this->direction->value : $this->direction,
            'amount' => (int) $this->amount,
            'balance_before' => (int) $this->balance_before,
            'balance_after' => (int) $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'description' => $this->description,
            'idempotency_key' => $this->idempotency_key,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s.u'),
            'previous_hash' => $previousHash,
        ];
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
