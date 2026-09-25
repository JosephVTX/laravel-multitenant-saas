<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status?->value,
            'plan' => $this->plan,
            'locale' => $this->locale,
            'settings' => $this->settings ?? [],
            'users_count' => $this->whenCounted('users'),
            'projects_count' => $this->whenCounted('projects'),
            'users' => $this->whenLoaded('users', fn () => UserResource::collection($this->users)),
            'projects' => $this->whenLoaded('projects', fn () => ProjectResource::collection($this->projects)),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
