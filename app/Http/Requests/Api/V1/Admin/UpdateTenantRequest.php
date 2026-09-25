<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tenant|null $tenant */
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('tenants', 'slug')->ignore($tenant?->getKey()),
            ],
            'plan' => ['sometimes', 'string', 'max:50'],
            'locale' => ['sometimes', 'string', 'max:8'],
            'status' => ['sometimes', Rule::enum(TenantStatus::class)],
            'settings' => ['sometimes', 'nullable', 'array'],
            'trial_ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
