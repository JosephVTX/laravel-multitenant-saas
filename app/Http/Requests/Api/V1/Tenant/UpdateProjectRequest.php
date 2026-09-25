<?php

namespace App\Http\Requests\Api\V1\Tenant;

use App\Enums\ProjectStatus;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:150', 'alpha_dash',
                Rule::unique('projects', 'slug')
                    ->where('tenant_id', app(TenantContext::class)->id())
                    ->whereNull('deleted_at')
                    ->ignore($this->route('project'), 'uuid'),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'meta' => ['sometimes', 'nullable', 'array'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
