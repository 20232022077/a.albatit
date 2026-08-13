<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', \App\Models\User::class); }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'username' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:users,username'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:12', 'confirmed'], 'role_ids' => ['nullable', 'array'], 'role_ids.*' => ['integer', Rule::exists('roles', 'id')]];
    }
}
