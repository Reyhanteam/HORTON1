<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class ServiceProviderAccount extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['credentials'=>'encrypted:array','metadata'=>'array'];} public function provider():BelongsTo{return $this->belongsTo(ServiceProvider::class,'service_provider_id');} public function services():HasMany{return $this->hasMany(Service::class,'provider_account_id');} }
