<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status?->value,
            'is_super_admin' => $this->isSuperAdmin(),
            'tenant_id' => $this->tenant_id,
            'tenant' => TenantResource::make($this->whenLoaded('tenant')),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()),
            'permissions' => $this->whenLoaded('roles', fn () => $this->getAllPermissions()->pluck('name')->values()),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
