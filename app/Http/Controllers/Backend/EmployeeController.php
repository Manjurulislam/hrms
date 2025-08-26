<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Employee/index');
    }

    public function store(EmployeeRequest $request)
    {
        try {
            Employee::create($request->validated());
            return to_route('employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function create()
    {
        $departments = Department::where('status', true)
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        $designations = Designation::orderBy('name')->get();

        return Inertia::render('Backend/Employee/create', [
            'departments'          => $departments,
            'designations'         => $designations,
            'genderOptions'        => $this->getGenderOptions(),
            'bloodGroupOptions'    => $this->getBloodGroupOptions(),
            'maritalStatusOptions' => $this->getMaritalStatusOptions()
        ]);
    }

    public function get(Request $request)
    {
        $query  = Employee::query()
            ->with(['department:id,name,company_id', 'department.company:id,name'])
            ->orderBy('first_name')
            ->orderBy('last_name');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    private function getGenderOptions(): array
    {
        return [
            ['value' => 'male', 'label' => 'Male'],
            ['value' => 'female', 'label' => 'Female'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    private function getBloodGroupOptions(): array
    {
        return [
            ['value' => 'A+', 'label' => 'A+'],
            ['value' => 'A-', 'label' => 'A-'],
            ['value' => 'B+', 'label' => 'B+'],
            ['value' => 'B-', 'label' => 'B-'],
            ['value' => 'AB+', 'label' => 'AB+'],
            ['value' => 'AB-', 'label' => 'AB-'],
            ['value' => 'O+', 'label' => 'O+'],
            ['value' => 'O-', 'label' => 'O-'],
        ];
    }

    private function getMaritalStatusOptions(): array
    {
        return [
            ['value' => 'single', 'label' => 'Single'],
            ['value' => 'married', 'label' => 'Married'],
            ['value' => 'divorced', 'label' => 'Divorced'],
            ['value' => 'widowed', 'label' => 'Widowed'],
        ];
    }

    public function edit(Employee $employee)
    {
        $departments  = Department::where('status', true)
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);
        $designations = Designation::orderBy('name')->get();

        $employee->load(['department:id,name,company_id', 'department.company:id,name']);

        return Inertia::render('Backend/Employee/edit', [
            'item'                 => $employee,
            'departments'          => $departments,
            'designations'         => $designations,
            'genderOptions'        => $this->getGenderOptions(),
            'bloodGroupOptions'    => $this->getBloodGroupOptions(),
            'maritalStatusOptions' => $this->getMaritalStatusOptions()
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            $employee->fill($request->validated())->save();
            return to_route('employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Employee $employee)
    {
        return $this->toggleModelStatus($employee);
    }
}
