<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return ['name' => ['required', 'string', 'max:255'], 'username' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user)], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)], 'password' => ['nullable', 'string', 'min:12', 'confirmed'], 'is_active' => ['required', 'boolean'], 'role_ids' => ['nullable', 'array'], 'role_ids.*' => ['integer', Rule::exists('roles', 'id')]];
    }
}
