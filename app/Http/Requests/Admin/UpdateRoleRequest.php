<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('role'));
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return ['name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')->ignore($role)], 'display_name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'permission_ids' => ['nullable', 'array'], 'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')]];
    }
}
