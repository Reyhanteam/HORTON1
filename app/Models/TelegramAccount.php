<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TelegramAccount extends Model { use HasFactory; protected $guarded=[]; protected function casts(): array { return ['is_bot'=>'boolean','is_active'=>'boolean','last_seen_at'=>'datetime']; } public function user(): BelongsTo { return $this->belongsTo(User::class); } }
