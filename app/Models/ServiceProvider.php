<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class ServiceProvider extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['configuration'=>'array','metadata'=>'array'];} public function accounts():HasMany{return $this->hasMany(ServiceProviderAccount::class);} public function services():HasMany{return $this->hasMany(Service::class);} public function scopeActive($q){return $q->where('status','active');} }
