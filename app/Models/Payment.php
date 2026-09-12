<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void { $payment->uuid ??= (string) Str::uuid(); });
    }

    protected function casts(): array
    {
        return ['method' => PaymentMethod::class, 'status' => PaymentStatus::class, 'amount' => 'integer', 'paid_at' => 'datetime', 'verified_at' => 'datetime', 'metadata' => 'array'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attempts(): HasMany { return $this->hasMany(PaymentAttempt::class); }
    public function callbacks(): HasMany { return $this->hasMany(PaymentCallback::class); }
}
