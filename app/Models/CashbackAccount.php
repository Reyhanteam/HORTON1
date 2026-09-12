<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class CashbackAccount extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['balance'=>'integer'];} public function user():BelongsTo{return $this->belongsTo(User::class);} public function transactions():HasMany{return $this->hasMany(CashbackTransaction::class);} }
