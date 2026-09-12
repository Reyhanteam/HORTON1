<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Invoice extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['subtotal'=>'integer','discount_amount'=>'integer','total_amount'=>'integer','issued_at'=>'datetime','due_at'=>'datetime','paid_at'=>'datetime','metadata'=>'array'];} public function order():BelongsTo{return $this->belongsTo(Order::class);} }
