<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:tenants,slug'],
            'plan' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:8'],
            'status' => ['nullable', Rule::enum(TenantStatus::class)],
            'settings' => ['nullable', 'array'],
            'trial_ends_at' => ['nullable', 'date'],

            'owner' => ['nullable', 'array'],
            'owner.name' => ['required_with:owner', 'string', 'max:255'],
            'owner.email' => ['required_with:owner', 'string', 'email', 'max:255', 'unique:users,email'],
            'owner.password' => ['required_with:owner', 'string', 'min:8', 'max:255'],
        ];
    }
}
