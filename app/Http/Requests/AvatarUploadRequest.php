<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Please select a photo to upload.',
            'avatar.image'    => 'The file must be an image.',
            'avatar.mimes'    => 'Only JPG, JPEG, PNG and WebP formats are allowed.',
            'avatar.max'      => 'Photo size must not exceed 2MB.',
        ];
    }
}
