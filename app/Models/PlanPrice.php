<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PlanPrice extends Model { use HasFactory; protected $guarded=[]; protected function casts(): array { return ['amount'=>'integer','is_default'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime']; } public function plan(): BelongsTo { return $this->belongsTo(Plan::class); } }
