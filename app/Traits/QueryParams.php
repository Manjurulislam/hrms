<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait QueryParams
{
    public function companyQuery($query, Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }));

        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function departmentQuery($query, Request $request)
    {
        $search    = $request->input('search');
        $companyId = $request->input('company_id');
        $status    = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where('name', 'like', "%{$search}%"));
        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function designationQuery($query, Request $request)
    {
        $search    = $request->input('search');
        $companyId = $request->input('company_id');
        $status    = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where('title', 'like', "%{$search}%"));
        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function holidayQuery($query, Request $request)
    {
        $search    = $request->input('search');
        $companyId = $request->input('company_id');
        $status    = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where('name', 'like', "%{$search}%"));
        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function leaveTypeQuery($query, Request $request)
    {
        $search    = $request->input('search');
        $companyId = $request->input('company_id');
        $status    = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where('name', 'like', "%{$search}%"));
        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function userQuery($query, Request $request)
    {
        $search = $request->input('search');
        $roleId = $request->input('role_id');
        $status = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }));

        $query->when(filled($roleId), fn($q) => $q->whereHas('roles', fn($q) => $q->where('role_id', $roleId)));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function roleQuery($query, Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where('name', 'like', "%{$search}%"));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }

    public function attendanceQuery($query, Request $request)
    {
        $search        = $request->input('search');
        $companyId     = $request->input('company_id');
        $departmentId  = $request->input('department_id');
        $designationId = $request->input('designation_id');
        $employeeId    = $request->input('employee_id');
        $status        = $request->input('status');
        $date          = $request->input('date');
        $month         = $request->input('month');

        $query->when(filled($search), fn($q) => $q->whereHas('employee', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('id_no', 'like', "%{$search}%");
        }));

        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when(filled($departmentId), fn($q) => $q->where('department_id', $departmentId));
        $query->when(filled($designationId), fn($q) => $q->whereHas('employee', fn($q) => $q->where('designation_id', $designationId)));
        $query->when(filled($employeeId), fn($q) => $q->where('employee_id', $employeeId));
        $query->when(filled($status), fn($q) => $q->where('status', $status));

        if (filled($date)) {
            $query->whereDate('attendance_date', $date);
        } elseif (filled($month)) {
            [$year, $m] = explode('-', $month);
            $query->whereYear('attendance_date', $year)->whereMonth('attendance_date', $m);
        } else {
            $query->whereDate('attendance_date', today());
        }

        return $query;
    }

    public function leaveRequestQuery($query, Request $request)
    {
        $search      = $request->input('search');
        $companyId   = $request->input('company_id');
        $employeeId  = $request->input('employee_id');
        $leaveTypeId = $request->input('leave_type_id');
        $status      = $request->input('status');
        $dateFrom    = $request->input('date_from');
        $dateTo      = $request->input('date_to');

        $query->when(filled($search), fn($q) => $q->whereHas('employee', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('id_no', 'like', "%{$search}%");
        }));

        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when(filled($employeeId), fn($q) => $q->where('employee_id', $employeeId));
        $query->when(filled($leaveTypeId), fn($q) => $q->where('leave_type_id', $leaveTypeId));
        $query->when(filled($status), fn($q) => $q->where('status', $status));

        // Leave periods overlapping the selected range
        $query->when(filled($dateFrom), fn($q) => $q->whereDate('ended_at', '>=', $dateFrom));
        $query->when(filled($dateTo), fn($q) => $q->whereDate('started_at', '<=', $dateTo));

        return $query;
    }

    public function attendanceRecordQuery($query, Request $request)
    {
        $status = $request->input('status');
        $date   = $request->input('date');
        $month  = $request->input('month');

        $query->when(filled($status), fn($q) => $q->where('status', $status));

        if (filled($date)) {
            $query->whereDate('attendance_date', $date);
        } elseif (filled($month)) {
            [$year, $m] = explode('-', $month);
            $query->whereYear('attendance_date', $year)->whereMonth('attendance_date', $m);
        } else {
            $query->whereYear('attendance_date', now()->year)->whereMonth('attendance_date', now()->month);
        }

        return $query;
    }

    public function employeeQuery($query, Request $request)
    {
        $search      = $request->input('search');
        $companyId   = $request->input('company_id');
        $deptId      = $request->input('department_id');
        $designation = $request->input('designation_id');
        $managerId   = $request->input('manager_id');
        $gender      = $request->input('gender');
        $joiningFrom = $request->input('joining_from');
        $joiningTo   = $request->input('joining_to');
        $empStatus   = $request->input('emp_status');
        $status      = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('id_no', 'like', "%{$search}%");
        }));

        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when(filled($deptId), fn($q) => $q->where('department_id', $deptId));
        $query->when(filled($designation), fn($q) => $q->where('designation_id', $designation));
        $query->when(filled($managerId), fn($q) => $q->where('manager_id', $managerId));
        $query->when(filled($gender), fn($q) => $q->where('gender', $gender));
        $query->when(filled($joiningFrom), fn($q) => $q->whereDate('joining_date', '>=', $joiningFrom));
        $query->when(filled($joiningTo), fn($q) => $q->whereDate('joining_date', '<=', $joiningTo));
        $query->when(filled($empStatus), fn($q) => $q->where('emp_status', $empStatus));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }
}
