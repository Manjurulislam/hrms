<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $designationId = $this->route('designation')?->id;

        return [
            'title'         => [
                'required',
                'string',
                'max:255',
                Rule::unique('designations', 'title')
                    ->where('company_id', $this->input('company_id'))
                    ->where('department_id', $this->input('department_id'))
                    ->ignore($designationId)
            ],
            'description'   => ['nullable', 'string', 'max:1000'],
            'parent_id'     => [
                'nullable',
                'integer',
                Rule::exists('designations', 'id')
                    ->where('company_id', $this->input('company_id'))
                    ->where('department_id', $this->input('department_id'))
                    ->where('status', true),
                function ($attribute, $value, $fail) use ($designationId) {
                    if ($value && $designationId && $value == $designationId) {
                        $fail('A designation cannot be its own parent.');
                    }

                    if ($value && $designationId && $this->wouldCreateCircularReference($designationId, $value)) {
                        $fail('This would create a circular reference in the hierarchy.');
                    }
                }
            ],
            'company_id'    => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where('status', true)
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('status', true),
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('company_id')) {
                        $department = Department::find($value);
                        if ($department && $department->company_id != $this->input('company_id')) {
                            $fail('The selected department does not belong to the selected company.');
                        }
                    }
                }
            ],
            'status'        => ['boolean'],
        ];
    }

    /**
     * Check if assigning this parent would create a circular reference
     */
    private function wouldCreateCircularReference($designationId, $parentId): bool
    {
        if (!$parentId) {
            return false;
        }

        // Get all ancestors of the proposed parent
        $ancestors = $this->getAncestors($parentId);

        // Check if the current designation is in the ancestor chain
        return in_array($designationId, $ancestors);
    }

    /**
     * Get all ancestors of a designation
     */
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

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title'         => 'designation title',
            'description'   => 'description',
            'parent_id'     => 'parent designation',
            'company_id'    => 'company',
            'department_id' => 'department',
            'status'        => 'status',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required'         => 'Designation title is required.',
            'title.unique'           => 'This designation title already exists in the selected department.',
            'description.max'        => 'Description cannot exceed 1000 characters.',
            'parent_id.exists'       => 'Selected parent designation is invalid or inactive.',
            'company_id.required'    => 'Please select a company.',
            'company_id.exists'      => 'Selected company is invalid or inactive.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists'   => 'Selected department is invalid or inactive.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation logic can be added here
            $this->validateHierarchyDepth($validator);
        });
    }

    /**
     * Validate hierarchy depth doesn't exceed reasonable limits
     */
    private function validateHierarchyDepth($validator): void
    {
        $parentId = $this->input('parent_id');
        if (!$parentId) {
            return;
        }

        $depth    = $this->calculateDepth($parentId);
        $maxDepth = 5; // Configurable max hierarchy depth

        if ($depth >= $maxDepth) {
            $validator->errors()->add(
                'parent_id',
                "Hierarchy depth cannot exceed {$maxDepth} levels."
            );
        }
    }

    /**
     * Calculate the depth of a designation in the hierarchy
     */
    private function calculateDepth($designationId, $depth = 0): int
    {
        if (!$designationId) {
            return $depth;
        }

        $designation = Designation::find($designationId);
        if (!$designation || !$designation->parent_id) {
            return $depth;
        }

        return $this->calculateDepth($designation->parent_id, $depth + 1);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation()
    {
        // Clean up the data after validation
        if (empty($this->input('parent_id'))) {
            $this->merge(['parent_id' => null]);
        }

        if (empty($this->input('description'))) {
            $this->merge(['description' => null]);
        }
    }
}
