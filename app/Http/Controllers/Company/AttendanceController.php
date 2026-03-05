<?php

namespace App\Http\Controllers\Company;

use App\Enums\AttendanceStatus;
use App\Exports\CompanyAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Backend\CompanyAttendanceService;
use App\Services\Backend\SharedService;
use App\Traits\CompanyAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly CompanyAttendanceService $service,
        protected readonly SharedService $shared
    ) {}

    public function index(): Response
    {
        $companyId = $this->activeCompanyId();

        return Inertia::render('Company/Attendance/index', [
            'departments'   => $this->shared->departments($companyId),
            'employees'     => $this->shared->employees(null, $companyId),
            'statusOptions' => collect(AttendanceStatus::cases())->map(fn($s) => [
                'label' => ucwords(str_replace('_', ' ', $s->value)),
                'value' => $s->value,
            ]),
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->service->list($request));
    }

    public function export(Request $request)
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        $filename = 'attendance_' . ($request->input('date') ?: $request->input('month', now()->format('Y-m-d'))) . '.xlsx';

        return Excel::download(new CompanyAttendanceExport($request), $filename);
    }

    public function show(Employee $employee): Response
    {
        $employee->load(['department:id,name', 'designation:id,title']);

        return Inertia::render('Company/Attendance/show', [
            'employee' => [
                'id'          => $employee->id,
                'name'        => $employee->full_name,
                'id_no'       => $employee->id_no,
                'department'  => $employee->department?->name ?? '-',
                'designation' => $employee->designation?->title ?? '-',
            ],
            'statusOptions' => collect(AttendanceStatus::cases())->map(fn($s) => [
                'label' => ucwords(str_replace('_', ' ', $s->value)),
                'value' => $s->value,
            ]),
        ]);
    }

    public function records(Request $request, Employee $employee): JsonResponse
    {
        $request->merge([
            'employee_id' => $employee->id,
            'company_id'  => $this->activeCompanyId(),
        ]);

        return response()->json($this->service->employeeAttendance($request));
    }
}
