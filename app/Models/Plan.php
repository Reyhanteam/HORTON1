<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Plan extends Model { use HasFactory; protected $guarded=[]; protected function casts(): array { return ['price'=>'integer','capacity_value'=>'integer','is_trial'=>'boolean','trial_duration_value'=>'integer','metadata'=>'array']; } public function product(): BelongsTo { return $this->belongsTo(Product::class); } public function prices(): HasMany { return $this->hasMany(PlanPrice::class); } public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); } public function services(): HasMany { return $this->hasMany(Service::class); } public function scopeActive($q){ return $q->where('status','active'); } public function scopeTrials($q){ return $q->where('is_trial',true); } }
