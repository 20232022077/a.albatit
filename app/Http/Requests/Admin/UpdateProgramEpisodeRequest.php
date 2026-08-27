<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Support\SafeFileUpload;
use App\Support\SafeYoutube;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission', 'content.update');
    }

    public function rules(): array
    {
        $episode = $this->route('episode');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('content_items', 'slug')->ignore($episode)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'video_url' => ['required', 'url', 'max:500'],
            'status' => ['required', Rule::in(ContentStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_IMAGE_KB],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! SafeYoutube::isValid($this->input('video_url'))) {
                $validator->errors()->add('video_url', 'يجب إدخال رابط فيديو يوتيوب صحيح.');
            }
        });
    }
}
