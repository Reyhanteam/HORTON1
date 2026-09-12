<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SupportMessage extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['attachments'=>'array'];} public function ticket():BelongsTo{return $this->belongsTo(SupportTicket::class,'ticket_id');} }
