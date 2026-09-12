<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Order extends Model { use HasFactory; protected $guarded=[]; protected function casts(): array { return ['subtotal'=>'integer','discount_amount'=>'integer','cashback_amount'=>'integer','wallet_amount'=>'integer','total_amount'=>'integer','paid_at'=>'datetime','cancelled_at'=>'datetime','completed_at'=>'datetime','metadata'=>'array']; } public function user(): BelongsTo{return $this->belongsTo(User::class);} public function items():HasMany{return $this->hasMany(OrderItem::class);} public function invoice():HasOne{return $this->hasOne(Invoice::class);} public function payments():HasMany{return $this->hasMany(Payment::class);} public function discountCode():BelongsTo{return $this->belongsTo(DiscountCode::class);} public function giftCode():BelongsTo{return $this->belongsTo(GiftCode::class);} public function services():HasMany{return $this->hasMany(Service::class);} }
