<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestFormRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Backend\LeaveApprovalService;
use App\Services\Backend\LeaveRequestService;
use App\Traits\ResolvesApprover;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    use ResolvesApprover;

    public function __construct(
        protected readonly LeaveRequestService  $service,
        protected readonly LeaveApprovalService $approvalService,
    ) {}

    private function employee(): Employee
    {
        return Auth::user()->employee;
    }

    public function index(): Response
    {
        $employee = $this->employee();

        return Inertia::render('Employee/Leave/index', [
            'statusOptions' => collect(\App\Enums\LeaveRequestStatus::cases())->map(fn($s) => [
                'label' => ucfirst(str_replace('_', ' ', $s->value)),
                'value' => $s->value,
            ]),
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        $employee = $this->employee();
        $request->merge(['employee_id' => $employee->id]);

        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        $employee = $this->employee();

        $leaveTypes = LeaveType::where('company_id', $employee->company_id)
            ->where('status', true)
            ->get(['id', 'name', 'max_per_year']);

        $balances = $this->service->getBalances($employee, now()->year);

        return Inertia::render('Employee/Leave/create', [
            'leaveTypes' => $leaveTypes,
            'balances'   => $balances,
        ]);
    }

    public function store(LeaveRequestFormRequest $request): RedirectResponse
    {
        try {
            $employee = $this->employee();
            $data = $request->validated();

            $this->service->store($employee, $data);

            return to_route('emp-leave.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to submit leave request.']);
        }
    }

    public function approvals(): Response
    {
        return Inertia::render('Employee/Leave/approvals');
    }

    public function getApprovals(Request $request): JsonResponse
    {
        $employee = $this->employee();

        $query = LeaveRequest::query()
            ->with([
                'employee:id,first_name,last_name,id_no',
                'leaveType:id,name',
            ])
            ->where('current_approver_id', $employee->id)
            ->orderBy('created_at', 'desc');

        return response()->json([
            'data'  => $query->get(),
            'total' => $query->count(),
        ]);
    }

    public function showApproval(LeaveRequest $leaveRequest): Response
    {
        $employee = $this->employee();

        if ($leaveRequest->current_approver_id !== $employee->id) {
            abort(403);
        }

        $leaveRequest->load([
            'employee:id,first_name,last_name,id_no',
            'leaveType:id,name',
            'currentApprover:id,first_name,last_name',
            'approvals' => fn($q) => $q->orderBy('created_at', 'asc'),
            'approvals.approver:id,first_name,last_name',
        ]);

        [$isCurrentApprover, $approverLevel] = $this->resolveApproverContext($leaveRequest);

        return Inertia::render('Employee/Leave/approval-show', [
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

            return to_route('emp-leave.approvals')->with('success', $result['message']);
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

            return to_route('emp-leave.approvals')->with('success', $result['message']);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to reject leave request.']);
        }
    }

    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = $this->employee();

        if ($leaveRequest->employee_id !== $employee->id) {
            abort(403);
        }

        $result = $this->service->cancel($leaveRequest);

        if (!$result) {
            return back()->withErrors(['error' => 'Only pending requests can be cancelled.']);
        }

        return back();
    }
}
