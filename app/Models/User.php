<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'status', 'email_verified_at'])]
#[Hidden(['password', 'remember_token', 'is_super_admin'])]
class User extends Authenticatable implements JWTSubject
{
    use BelongsToTenant, HasFactory, HasRoles, HasUuid, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'tid' => $this->tenant_id,
            'sad' => $this->isSuperAdmin(),
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function belongsToTenant(Tenant|int|string|null $tenant): bool
    {
        $id = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return $this->tenant_id !== null && (int) $this->tenant_id === (int) $id;
    }
}
