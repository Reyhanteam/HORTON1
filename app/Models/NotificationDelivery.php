<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class NotificationDelivery extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['attempts'=>'integer','sent_at'=>'datetime','failed_at'=>'datetime'];} public function notification():BelongsTo{return $this->belongsTo(Notification::class);} }
