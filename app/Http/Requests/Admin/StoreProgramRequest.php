<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Support\SafeFileUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('content_items', 'slug')],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'presenter' => ['nullable', 'string', 'max:255'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_IMAGE_KB],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }
}
