<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name','username','email','phone','password','status','locale','timezone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function telegramAccount(): HasOne { return $this->hasOne(TelegramAccount::class); }
    public function profile(): HasOne { return $this->hasOne(UserProfile::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function wallets(): HasMany { return $this->hasMany(Wallet::class); }
    public function walletTransactions(): HasMany { return $this->hasMany(WalletTransaction::class); }
    public function services(): HasMany { return $this->hasMany(Service::class); }
    public function discountUsages(): HasMany { return $this->hasMany(DiscountUsage::class); }
    public function giftCodeRedemptions(): HasMany { return $this->hasMany(GiftCodeRedemption::class); }
    public function referralAccount(): HasOne { return $this->hasOne(ReferralAccount::class); }
    public function referrals(): HasMany { return $this->hasMany(Referral::class, 'referrer_user_id'); }
    public function referral(): HasOne { return $this->hasOne(Referral::class, 'referred_user_id'); }
    public function cashbackAccounts(): HasMany { return $this->hasMany(CashbackAccount::class); }
    public function cashbackTransactions(): HasMany { return $this->hasMany(CashbackTransaction::class); }
    public function supportTickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active->value;
    }

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
