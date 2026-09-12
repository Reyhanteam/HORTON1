<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Campaign extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['starts_at'=>'datetime','ends_at'=>'datetime','usage_limit'=>'integer','used_count'=>'integer','configuration'=>'array'];} }
