<?php

namespace App\Http\Requests\Api;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;

class ApiCheckOutRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'long'     => ['nullable', 'numeric', 'between:-180,180'],
            'note'     => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function getSanitizedData(): array
    {
        return [
            'location' => $this->input('location', 'office'),
            'lat'      => $this->input('lat'),
            'long'     => $this->input('long'),
            'note'     => $this->input('note'),
        ];
    }
}
