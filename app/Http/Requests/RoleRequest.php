<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($this->route('role')?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique'   => 'This role name already exists.',
        ];
    }
}
