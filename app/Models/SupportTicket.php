<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class SupportTicket extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['last_message_at'=>'datetime','closed_at'=>'datetime'];} public function user():BelongsTo{return $this->belongsTo(User::class);} public function assignedAdmin():BelongsTo{return $this->belongsTo(AdminUser::class,'assigned_admin_id');} public function messages():HasMany{return $this->hasMany(SupportMessage::class,'ticket_id');} }
