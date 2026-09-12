<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ServiceOperation extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['started_at'=>'datetime','completed_at'=>'datetime','request_metadata'=>'array','response_metadata'=>'array'];} public function service():BelongsTo{return $this->belongsTo(Service::class);} }
