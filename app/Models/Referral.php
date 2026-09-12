<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Referral extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['registered_at'=>'datetime','qualified_at'=>'datetime','metadata'=>'array'];} public function referrer():BelongsTo{return $this->belongsTo(User::class,'referrer_user_id');} public function referred():BelongsTo{return $this->belongsTo(User::class,'referred_user_id');} }
