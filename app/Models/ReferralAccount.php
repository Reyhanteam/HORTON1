<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class ReferralAccount extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['commission_rate'=>'decimal:2','cashback_rate'=>'decimal:2'];} public function user():BelongsTo{return $this->belongsTo(User::class);} public function referrals():HasMany{return $this->hasMany(Referral::class,'referrer_user_id','user_id');} }
