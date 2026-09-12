<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PaymentAttempt extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['amount'=>'integer','metadata'=>'array'];} public function payment():BelongsTo{return $this->belongsTo(Payment::class);} }
