<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class OrderItem extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['quantity'=>'integer','unit_price'=>'integer','discount_amount'=>'integer','total_amount'=>'integer','metadata'=>'array'];} public function order():BelongsTo{return $this->belongsTo(Order::class);} public function product():BelongsTo{return $this->belongsTo(Product::class);} public function plan():BelongsTo{return $this->belongsTo(Plan::class);} public function services():HasMany{return $this->hasMany(Service::class);} }
