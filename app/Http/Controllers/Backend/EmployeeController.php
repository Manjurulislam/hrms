<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Imports\EmployeeImport;
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
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class EmployeeController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        [$companies, $departments, $designations] = $this->getParams();
        return Inertia::render('Backend/Employee/index', [
            'companies'    => $companies,
            'departments'  => $departments,
            'designations' => $designations,
        ]);
    }

    protected function getParams()
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

        return [$companies, $departments, $designations];
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

    public function import(Request $request)
    {
        $request->validate([
            'file'          => 'required|mimes:xlsx,xls,csv|max:2048',
            'company_id'    => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        try {
            $company    = $request->get('company_id');
            $department = $request->get('department_id');
            $import     = new EmployeeImport($company, $department);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getImportedCount();
            $failureCount = $import->failures()->count();
            $errorCount   = $import->errors()->count();

            $message = "Import completed! {$successCount} employees imported successfully.";

            if ($failureCount > 0) {
                $message .= " {$failureCount} rows failed validation.";
            }

            if ($errorCount > 0) {
                $message .= " {$errorCount} rows had errors.";
            }

            // Store failures in session for display if needed
            if ($import->failures()->count() > 0) {
                session()->put('import_failures', $import->failures()->toArray());
            }

            return redirect()->back()->with('success', $message);

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }


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
        [$companies, $departments, $designations] = $this->getParams();

        return Inertia::render('Backend/Employee/create', [
            'companies'            => $companies,
            'departments'          => $departments,
            'designations'         => $designations,
            'genderOptions'        => $this->getGenderOptions(),
            'bloodGroupOptions'    => $this->getBloodGroupOptions(),
            'maritalStatusOptions' => $this->getMaritalStatusOptions()
        ]);
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
        [$companies, $departments, $designations] = $this->getParams();

        $employee->load([
            'department:id,name,company_id',
            'department.company:id,name',
            'user:id,employee_id,email,status'
        ]);
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
