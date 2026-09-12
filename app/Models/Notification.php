<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class Notification extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['data'=>'array','read_at'=>'datetime'];} public function user():BelongsTo{return $this->belongsTo(User::class);} public function deliveries():HasMany{return $this->hasMany(NotificationDelivery::class);} public function scopeUnread($q){return $q->whereNull('read_at');} }
