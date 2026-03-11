<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.required'    => 'Month is required.',
            'month.date_format' => 'Month must be in YYYY-MM format.',
        ];
    }
}
