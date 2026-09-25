<?php

namespace App\Http\Requests\Api\V1\Tenant;

use App\Enums\ProjectStatus;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:150', 'alpha_dash',
                Rule::unique('projects', 'slug')
                    ->where('tenant_id', app(TenantContext::class)->id())
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'meta' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
