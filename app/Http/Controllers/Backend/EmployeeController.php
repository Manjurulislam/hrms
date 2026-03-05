<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Imports\EmployeeImport;
use App\Models\Employee;
use App\Services\Backend\EmployeeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function __construct(
        protected readonly EmployeeService $service
    ) {}

    public function index(): Response
    {
        $data = $this->service->formData();

        return Inertia::render('Backend/Employee/index', [
            'companies'        => $data['companies'],
            'departments'      => $data['departments'],
            'empStatusOptions' => $data['empStatusOptions'],
            'defaultCompanyId' => $data['companies']->first()?->id,
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Employee/create', $this->service->formData());
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create employee.']);
        }
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('Backend/Employee/edit', $this->service->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        try {
            $this->service->update($employee, $request->validated());

            return to_route('employees.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update employee.']);
        }
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        try {
            $this->service->delete($employee);

            return to_route('employees.index');
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

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file'          => 'required|mimes:xlsx,xls,csv|max:2048',
            'company_id'    => 'required|exists:companies,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        try {
            $import = new EmployeeImport(
                $request->input('company_id'),
                $request->input('department_id')
            );

            Excel::import($import, $request->file('file'));

            $message = "{$import->getImportedCount()} employees imported successfully.";

            if ($import->failures()->isNotEmpty()) {
                $message .= " {$import->failures()->count()} rows failed.";
                session()->put('import_failures', $import->failures()->toArray());
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
