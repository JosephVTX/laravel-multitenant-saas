<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user'), 'uuid'),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:255'],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'is_super_admin' => ['sometimes', 'boolean'],
        ];
    }
}
