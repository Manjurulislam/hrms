<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class EmployeeController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Employee/index');
    }

    /**
     * @throws Throwable
     */
    public function store(EmployeeRequest $request)
    {
        DB::beginTransaction();
        try {
            // Create employee
            $employeeData = $request->validated();
            $employee     = Employee::create($employeeData);

            // Sync designations
            $employee->designations()->sync($request->designations);

            // Create user account if requested
            if ($request->filled('password')) {
                $this->createUserAccount($employee, $request);
            }

            DB::commit();
            return to_route('employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();
            return redirect()->back();
        }
    }

    public function create()
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::where('status', true)
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        $designations = Designation::where('status', true)
            ->orderBy('title')
            ->get(['id', 'title', 'company_id', 'department_id']);

        return Inertia::render('Backend/Employee/create', [
            'companies'            => $companies,
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
            ->with(['department', 'designations', 'user:id,employee_id,email,status'])
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

    /**
     * Create user account for employee
     */
    private function createUserAccount(Employee $employee, EmployeeRequest $request): User
    {
        return User::create([
            'name'        => $employee->first_name . ' ' . $employee->last_name,
            'email'       => $employee->email,
            'password'    => Hash::make($request->password),
            'employee_id' => $employee->id,
            'status'      => $employee->status,
        ]);
    }

    public function edit(Employee $employee)
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::where('status', true)
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        $designations = Designation::where('status', true)
            ->orderBy('title')
            ->get(['id', 'title', 'company_id', 'department_id']);

        $employee->load(['department:id,name,company_id', 'department.company:id,name', 'user:id,employee_id,email,status']);

        // Get selected designations
        $selectedDesignations = $employee->designations->pluck('id')->toArray();

        return Inertia::render('Backend/Employee/edit', [
            'item'                 => $employee,
            'companies'            => $companies,
            'departments'          => $departments,
            'designations'         => $designations,
            'selectedDesignations' => $selectedDesignations,
            'genderOptions'        => $this->getGenderOptions(),
            'bloodGroupOptions'    => $this->getBloodGroupOptions(),
            'maritalStatusOptions' => $this->getMaritalStatusOptions()
        ]);
    }

    public function destroy(Employee $employee)
    {
        try {
            // Also delete associated user if exists
            if ($employee->user) {
                $employee->user->delete();
            }
            $employee->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Employee $employee)
    {
        $result = $this->toggleModelStatus($employee);

        // Also toggle user status if user exists
        if ($employee->user) {
            $employee->user->update(['status' => $employee->status]);
        }

        return $result;
    }

    /**
     * @throws Throwable
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        DB::beginTransaction();
        try {
            // Update employee
            $employeeData = $request->validated();
            $employee->fill($employeeData)->save();

            // Sync designations
            $employee->designations()->sync($request->designations);

            // Handle user account
            if ($employee->user) {
                // Update existing user
                $this->updateUserAccount($employee, $request);
            }
            DB::commit();
            return to_route('employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();
            return redirect()->back();
        }
    }

    /**
     * Update existing user account
     */
    private function updateUserAccount(Employee $employee, EmployeeRequest $request): void
    {
        $updateData = [
            'name'   => $employee->first_name . ' ' . $employee->last_name,
            'email'  => $employee->email,
            'status' => $employee->status,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $employee->user->update($updateData);
    }
}
