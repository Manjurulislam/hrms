<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Services\Backend\DepartmentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(
        protected readonly DepartmentService $service
    ) {}

    public function index(): Response
    {
        $companies = $this->service->formData()['companies'];

        return Inertia::render('Backend/Department/index', [
            'companies'        => $companies,
            'defaultCompanyId' => $companies->first()?->id,
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Department/create', $this->service->formData());
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create department.']);
        }
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('Backend/Department/edit', $this->service->formData($department));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        try {
            $this->service->update($department, $request->validated());

            return to_route('departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update department.']);
        }
    }

    public function destroy(Department $department): RedirectResponse
    {
        try {
            $this->service->delete($department);

            return to_route('departments.index');
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
