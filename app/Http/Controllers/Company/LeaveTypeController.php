<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\Backend\LeaveTypeService;
use App\Traits\CompanyAuth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly LeaveTypeService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Company/LeaveType/index');
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Company/LeaveType/create');
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->create($data);

            return to_route('company.leave-types.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create leave type.']);
        }
    }

    public function edit(LeaveType $leaveType): Response
    {
        return Inertia::render('Company/LeaveType/edit', [
            'item' => $leaveType,
        ]);
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->update($leaveType, $data);

            return to_route('company.leave-types.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update leave type.']);
        }
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        try {
            $this->service->delete($leaveType);

            return to_route('company.leave-types.index');
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
