<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWallPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.update');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'text' => trim(strip_tags((string) $this->input('text'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'is_pinned' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $url = (string) $this->input('video_url');
            if ($url === '') {
                return;
            }

            $host = parse_url($url, PHP_URL_HOST);
            if (! $host || ! preg_match('/(^|\.)(youtube\.com|youtu\.be)$/i', $host)) {
                $validator->errors()->add('video_url', 'يجب إدخال رابط فيديو يوتيوب صحيح.');
            }
        });
    }
}
