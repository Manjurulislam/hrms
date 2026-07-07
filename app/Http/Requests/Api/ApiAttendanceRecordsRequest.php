<?php

namespace App\Http\Requests\Api;

use App\Traits\AttendanceValidation;
use Illuminate\Foundation\Http\FormRequest;

class ApiAttendanceRecordsRequest extends FormRequest
{
    use AttendanceValidation;

    public function authorize(): bool
    {
        return (bool) $this->getEmployee();
    }

    public function rules(): array
    {
        return [
            'months' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function months(): int
    {
        return (int) $this->input('months', 3);
    }
}
