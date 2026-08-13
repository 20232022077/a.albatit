<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', \App\Models\Role::class); }
    public function rules(): array { return ['name' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:roles,name'], 'display_name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'permission_ids' => ['nullable', 'array'], 'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')]]; }
}
