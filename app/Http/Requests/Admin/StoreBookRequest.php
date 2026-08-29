<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Support\SafeFileUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
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
            'author_name' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('books', 'isbn')],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:9999'],
            'pages_count' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_IMAGE_KB],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:'.SafeFileUpload::MAX_DOCUMENT_KB],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }
}
