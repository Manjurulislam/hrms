<?php

namespace App\Http\Controllers\Backend;

use App\Enums\AttendanceStatus;
use App\Exports\CompanyAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Backend\AttendanceManagementService;
use App\Services\Backend\SharedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function __construct(
        protected readonly AttendanceManagementService $service,
        protected readonly SharedService $shared
    ) {}

    public function index(): Response
    {
        $companies = $this->shared->companies();

        return Inertia::render('Backend/Attendance/index', [
            'companies'        => $companies,
            'departments'      => $this->shared->departments(),
            'employees'        => $this->shared->employees(),
            'defaultCompanyId' => $companies->first()?->id,
            'statusOptions'    => collect(AttendanceStatus::cases())->map(fn($s) => [
                'label' => ucwords(str_replace('_', ' ', $s->value)),
                'value' => $s->value,
            ]),
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function export(Request $request)
    {
        $filename = 'attendance_' . ($request->input('date') ?: $request->input('month', now()->format('Y-m-d'))) . '.xlsx';

        return Excel::download(new CompanyAttendanceExport($request), $filename);
    }

    public function show(Employee $employee): Response
    {
        $employee->load(['department:id,name', 'designation:id,title']);

        return Inertia::render('Backend/Attendance/show', [
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
        $request->merge(['employee_id' => $employee->id]);

        return response()->json($this->service->employeeAttendance($request));
    }
}
