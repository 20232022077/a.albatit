<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuranCentralityItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.update');
    }

    public function rules(): array
    {
        $item = $this->route('item');

        return [
            'type' => ['required', 'in:article,study,video,pdf,image'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('content_items', 'slug')->ignore($item)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,mp4,webm,mov', 'max:51200'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }
}
