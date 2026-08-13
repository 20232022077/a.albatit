<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('tags', 'name')],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('tags', 'slug')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
