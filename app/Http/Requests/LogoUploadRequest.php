<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Please select a logo to upload.',
            'logo.file'     => 'The logo must be a valid file.',
            'logo.mimes'    => 'Only JPG, JPEG, PNG, WebP and SVG formats are allowed.',
            'logo.max'      => 'Logo size must not exceed 2MB.',
        ];
    }
}
