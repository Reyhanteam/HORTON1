<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'capacity' => 'integer',
            'used_capacity' => 'integer',
            'is_trial' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function providerAccount(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderAccount::class, 'provider_account_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ServiceOperation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ServiceStatus::ACTIVE);
    }
}
