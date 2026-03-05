<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Services\Backend\DepartmentService;
use App\Traits\CompanyAuth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly DepartmentService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Company/Department/index');
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Company/Department/create');
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->create($data);

            return to_route('company.departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create department.']);
        }
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('Company/Department/edit', [
            'item' => $department,
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->update($department, $data);

            return to_route('company.departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update department.']);
        }
    }

    public function destroy(Department $department): RedirectResponse
    {
        try {
            $this->service->delete($department);

            return to_route('company.departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete department.']);
        }
    }

    public function toggleStatus(Department $department): JsonResponse
    {
        try {
            $status = $this->service->toggle($department);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
