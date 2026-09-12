<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class BotMessage extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];} public function scopeActive($q){return $q->where('is_active',true);} }
