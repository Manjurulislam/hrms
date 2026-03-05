<?php

namespace App\Http\Requests;

use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $designationId = $this->route('designation')?->id;

        return [
            'title'       => [
                'required', 'string', 'max:255',
                Rule::unique('designations', 'title')
                    ->where('company_id', $this->input('company_id'))
                    ->ignore($designationId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'level'       => ['required', 'integer', 'min:1', 'max:10'],
            'parent_id'   => [
                'nullable', 'integer',
                Rule::exists('designations', 'id')
                    ->where('company_id', $this->input('company_id'))
                    ->where('status', true),
                function ($attribute, $value, $fail) use ($designationId) {
                    if ($value && $designationId && $value == $designationId) {
                        $fail('A designation cannot be its own parent.');
                    }

                    if ($value && $designationId && $this->wouldCreateCircularReference($designationId, $value)) {
                        $fail('This would create a circular reference in the hierarchy.');
                    }
                },
            ],
            'company_id'  => ['required', 'exists:companies,id'],
            'status'      => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'       => 'designation title',
            'description' => 'description',
            'level'       => 'level',
            'parent_id'   => 'parent designation',
            'company_id'  => 'company',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'      => 'Designation title is required.',
            'title.unique'        => 'This designation title already exists for the selected company.',
            'level.required'      => 'Designation level is required.',
            'level.min'           => 'Level must be at least 1.',
            'level.max'           => 'Level cannot exceed 10.',
            'parent_id.exists'    => 'Selected parent designation is invalid or inactive.',
            'company_id.required' => 'Please select a company.',
            'company_id.exists'   => 'Selected company is invalid or inactive.',
        ];
    }

    private function wouldCreateCircularReference($designationId, $parentId): bool
    {
        if (!$parentId) {
            return false;
        }

        $ancestors = $this->getAncestors($parentId);

        return in_array($designationId, $ancestors);
    }

    private function getAncestors($designationId, $ancestors = []): array
    {
        if (!$designationId || in_array($designationId, $ancestors)) {
            return $ancestors;
        }

        $designation = Designation::find($designationId);

        if (!$designation || !$designation->parent_id) {
            return $ancestors;
        }

        $ancestors[] = $designationId;

        return $this->getAncestors($designation->parent_id, $ancestors);
    }

    protected function passedValidation()
    {
        if (empty($this->input('parent_id'))) {
            $this->merge(['parent_id' => null]);
        }

        if (empty($this->input('description'))) {
            $this->merge(['description' => null]);
        }
    }
}
