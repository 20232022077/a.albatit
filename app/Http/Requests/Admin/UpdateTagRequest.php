<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.update');
    }

    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('tags', 'name')->ignore($tag)],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('tags', 'slug')->ignore($tag)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
