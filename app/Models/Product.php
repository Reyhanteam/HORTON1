<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model { use HasFactory; protected $guarded=[]; protected function casts(): array { return ['metadata'=>'array']; } public function category(): BelongsTo { return $this->belongsTo(Category::class); } public function plans(): HasMany { return $this->hasMany(Plan::class); } public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); } public function scopeActive($q){ return $q->where('status','active'); } }
