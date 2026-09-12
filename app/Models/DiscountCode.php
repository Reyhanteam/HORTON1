<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class DiscountCode extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['value'=>'integer','minimum_order_amount'=>'integer','maximum_discount_amount'=>'integer','usage_limit'=>'integer','usage_limit_per_user'=>'integer','used_count'=>'integer','starts_at'=>'datetime','expires_at'=>'datetime','is_active'=>'boolean','metadata'=>'array'];} public function usages():HasMany{return $this->hasMany(DiscountUsage::class);} public function orders():HasMany{return $this->hasMany(Order::class);} }
