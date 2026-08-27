<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Support\SafeFileUpload;
use App\Support\SafeYoutube;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuraniyatItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.create');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:article,khatira,fawaid,video,pdf'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('content_items', 'slug')],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_IMAGE_KB],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:'.SafeFileUpload::MAX_DOCUMENT_KB],
            'video_url' => ['nullable', 'url', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') === 'pdf' && ! $this->hasFile('attachment')) {
                $validator->errors()->add('attachment', 'يجب رفع ملف PDF لهذا النوع من المحتوى.');
            }

            if ($this->input('type') === 'video' && ! SafeYoutube::isValid($this->input('video_url'))) {
                $validator->errors()->add('video_url', 'يجب إدخال رابط فيديو يوتيوب صحيح.');
            }
        });
    }
}
