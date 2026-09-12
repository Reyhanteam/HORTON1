<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AuditLog extends Model { use HasFactory; public $timestamps=false; protected $guarded=[]; protected function casts():array{return ['old_values'=>'array','new_values'=>'array','created_at'=>'datetime'];} public function adminUser():BelongsTo{return $this->belongsTo(AdminUser::class);} }
