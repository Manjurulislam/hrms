<?php

namespace App\Http\Controllers\Backend;

use App\Exports\LeaveRequestExport;
use App\Http\Controllers\Controller;
use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Backend\LeaveApprovalService;
use App\Services\Backend\LeaveRequestService;
use App\Services\Backend\SharedService;
use App\Traits\ResolvesApprover;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class LeaveRequestController extends Controller
{
    use ResolvesApprover;
    public function __construct(
        protected readonly LeaveRequestService $requestService,
        protected readonly LeaveApprovalService $approvalService,
        protected readonly SharedService $shared
    ) {}

    public function index(): Response
    {
        $companies = $this->shared->companies();

        return Inertia::render('Backend/LeaveRequest/index', [
            'companies'        => $companies,
            'defaultCompanyId' => $companies->first()?->id,
            'employees'        => Employee::where('status', true)->get(['id', 'first_name', 'last_name']),
            'leaveTypes'       => LeaveType::where('status', true)->get(['id', 'name']),
            'statusOptions'    => collect(LeaveRequestStatus::cases())->map(fn($s) => [
                'label' => ucfirst(str_replace('_', ' ', $s->value)),
                'value' => $s->value,
            ]),
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->requestService->list($request));
    }

    public function export(Request $request)
    {
        $filename = 'leave_requests_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new LeaveRequestExport($request), $filename);
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

        [$isCurrentApprover, $approverLevel] = $this->resolveApproverContext($leaveRequest);

        return Inertia::render('Backend/LeaveRequest/show', [
            'leaveRequest'      => $leaveRequest,
            'isCurrentApprover' => $isCurrentApprover,
            'approverLevel'     => $approverLevel,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            if (!$this->canActOnLeaveRequest($leaveRequest)) {
                return back()->withErrors(['error' => 'You are not authorized to approve this request.']);
            }

            $approver = $this->getApproverEmployee($leaveRequest);

            $result = $this->approvalService->approve(
                $leaveRequest,
                $approver,
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
            if (!$this->canActOnLeaveRequest($leaveRequest)) {
                return back()->withErrors(['error' => 'You are not authorized to reject this request.']);
            }

            $approver = $this->getApproverEmployee($leaveRequest);

            $result = $this->approvalService->reject(
                $leaveRequest,
                $approver,
                $request->input('remarks')
            );

            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to reject leave request.']);
        }
    }
}
