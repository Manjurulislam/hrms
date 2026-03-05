<?php

namespace App\Http\Controllers\Company;

use App\Enums\BloodGroup;
use App\Enums\EmpStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Services\Backend\EmployeeService;
use App\Services\Backend\SharedService;
use App\Traits\CompanyAuth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly EmployeeService $service,
        protected readonly SharedService   $shared
    ) {}

    public function index(): Response
    {
        $companyId = $this->activeCompanyId();

        return Inertia::render('Company/Employee/index', [
            'departments'      => $this->shared->departments($companyId),
            'empStatusOptions' => EmpStatus::toOptions(),
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        $companyId = $this->activeCompanyId();

        return Inertia::render('Company/Employee/create', [
            'departments'          => $this->shared->departments($companyId),
            'designations'         => $this->shared->designations(null, $companyId),
            'employees'            => $this->shared->employees(null, $companyId),
            'genderOptions'        => Gender::toOptions(),
            'bloodGroupOptions'    => BloodGroup::toOptions(),
            'maritalStatusOptions' => MaritalStatus::toOptions(),
            'empStatusOptions'     => EmpStatus::toOptions(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->create($data);

            return to_route('company.employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create employee.']);
        }
    }

    public function edit(Employee $employee): Response
    {
        $companyId = $this->activeCompanyId();

        $employee->load(['department:id,name,company_id', 'designation:id,title', 'manager:id,first_name,last_name', 'user:id,employee_id,email,status']);

        return Inertia::render('Company/Employee/edit', [
            'item'                 => $employee,
            'departments'          => $this->shared->departments($companyId),
            'designations'         => $this->shared->designations(null, $companyId),
            'employees'            => $this->shared->employees($employee->id, $companyId),
            'genderOptions'        => Gender::toOptions(),
            'bloodGroupOptions'    => BloodGroup::toOptions(),
            'maritalStatusOptions' => MaritalStatus::toOptions(),
            'empStatusOptions'     => EmpStatus::toOptions(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->update($employee, $data);

            return to_route('company.employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update employee.']);
        }
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        try {
            $this->service->delete($employee);

            return to_route('company.employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete employee.']);
        }
    }

    public function toggleStatus(Employee $employee): JsonResponse
    {
        try {
            $status = $this->service->toggle($employee);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
