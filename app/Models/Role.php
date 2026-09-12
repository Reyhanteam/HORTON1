<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Role extends Model { use HasFactory; protected $guarded=[]; public function admins():BelongsToMany{return $this->belongsToMany(AdminUser::class,'role_user');} public function permissions():BelongsToMany{return $this->belongsToMany(Permission::class,'permission_role');} }
