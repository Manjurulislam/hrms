<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\Backend\LeaveTypeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function __construct(
        protected readonly LeaveTypeService $service
    ) {}

    public function index(): Response
    {
        $companies = $this->service->formData()['companies'];

        return Inertia::render('Backend/LeaveType/index', [
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
        return Inertia::render('Backend/LeaveType/create', $this->service->formData());
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('leave-types.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create leave type.']);
        }
    }

    public function edit(LeaveType $leaveType): Response
    {
        return Inertia::render('Backend/LeaveType/edit', $this->service->formData($leaveType));
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        try {
            $this->service->update($leaveType, $request->validated());

            return to_route('leave-types.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update leave type.']);
        }
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        try {
            $this->service->delete($leaveType);

            return to_route('leave-types.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete leave type.']);
        }
    }

    public function toggleStatus(LeaveType $leaveType): JsonResponse
    {
        try {
            $status = $this->service->toggle($leaveType);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
