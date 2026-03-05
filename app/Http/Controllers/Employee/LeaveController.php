<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestFormRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Backend\LeaveRequestService;
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
    public function __construct(
        protected readonly LeaveRequestService $service
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
