<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Backend\LeaveApprovalService;
use App\Services\Backend\LeaveRequestService;
use App\Traits\CompanyAuth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly LeaveRequestService $requestService,
        protected readonly LeaveApprovalService $approvalService
    ) {}

    public function index(): Response
    {
        $companyId = $this->activeCompanyId();

        $employees = Employee::where('company_id', $companyId)
            ->where('status', true)
            ->get(['id', 'first_name', 'last_name']);

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->where('status', true)
            ->get(['id', 'name']);

        return Inertia::render('Company/LeaveRequest/index', [
            'employees'     => $employees,
            'leaveTypes'    => $leaveTypes,
            'statusOptions' => collect(LeaveRequestStatus::cases())->map(fn($s) => [
                'label' => ucfirst(str_replace('_', ' ', $s->value)),
                'value' => $s->value,
            ]),
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->requestService->list($request));
    }

    public function show(LeaveRequest $leaveRequest): Response
    {
        $leaveRequest->load([
            'employee:id,first_name,last_name,id_no',
            'leaveType:id,name',
            'currentApprover:id,first_name,last_name',
            'approvals' => fn($q) => $q->orderBy('created_at', 'asc'),
            'approvals.approver:id,first_name,last_name',
        ]);

        $currentEmployee = Auth::user()->employee;
        $isCurrentApprover = $leaveRequest->current_approver_id === $currentEmployee->id;
        $approverLevel = $currentEmployee->designation?->level ?? 99;

        return Inertia::render('Company/LeaveRequest/show', [
            'leaveRequest'     => $leaveRequest,
            'isCurrentApprover' => $isCurrentApprover,
            'approverLevel'    => $approverLevel,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $employee = Auth::user()->employee;

            if ($leaveRequest->current_approver_id !== $employee->id) {
                return back()->withErrors(['error' => 'You are not the current approver.']);
            }

            $result = $this->approvalService->approve(
                $leaveRequest,
                $employee,
                $request->input('remarks'),
                $request->boolean('forward')
            );

            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to approve leave request.']);
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $employee = Auth::user()->employee;

            if ($leaveRequest->current_approver_id !== $employee->id) {
                return back()->withErrors(['error' => 'You are not the current approver.']);
            }

            $result = $this->approvalService->reject(
                $leaveRequest,
                $employee,
                $request->input('remarks')
            );

            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to reject leave request.']);
        }
    }
}
