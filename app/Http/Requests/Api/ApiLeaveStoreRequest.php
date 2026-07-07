<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApiLeaveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->employee;
    }

    /**
     * Mirrors the web LeaveRequestFormRequest so the service receives the same shape.
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'title'         => ['required', 'string', 'max:255'],
            'notes'         => ['required', 'string'],
            'started_at'    => ['required', 'date'],
            'ended_at'      => ['required', 'date', 'after_or_equal:started_at'],
        ];
    }
}
