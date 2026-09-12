<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class BotChannel extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['telegram_chat_id'=>'integer','is_required'=>'boolean','is_active'=>'boolean','sort_order'=>'integer'];} public function scopeActive($q){return $q->where('is_active',true);} public function scopeRequired($q){return $q->where('is_required',true);} }
