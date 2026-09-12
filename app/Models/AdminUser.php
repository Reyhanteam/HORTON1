<?php

namespace App\Models;

use App\Enums\AdminStatus;
use Database\Factories\AdminUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class, 'role_user'); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
    public function assignedTickets(): HasMany { return $this->hasMany(SupportTicket::class, 'assigned_admin_id'); }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }

    public function isActive(): bool
    {
        return $this->status === AdminStatus::Active;
    }

    protected function casts(): array
    {
        return [
            'status' => AdminStatus::class,
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }
}
