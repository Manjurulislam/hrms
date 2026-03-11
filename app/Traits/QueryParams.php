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
        $search       = $request->input('search');
        $companyId    = $request->input('company_id');
        $departmentId = $request->input('department_id');
        $employeeId   = $request->input('employee_id');
        $status       = $request->input('status');
        $date         = $request->input('date');
        $month        = $request->input('month');

        $query->when(filled($search), fn($q) => $q->whereHas('employee', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('id_no', 'like', "%{$search}%");
        }));

        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when(filled($departmentId), fn($q) => $q->where('department_id', $departmentId));
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
        $companyId   = $request->input('company_id');
        $employeeId  = $request->input('employee_id');
        $leaveTypeId = $request->input('leave_type_id');
        $status      = $request->input('status');

        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when(filled($employeeId), fn($q) => $q->where('employee_id', $employeeId));
        $query->when(filled($leaveTypeId), fn($q) => $q->where('leave_type_id', $leaveTypeId));
        $query->when(filled($status), fn($q) => $q->where('status', $status));

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
        $search    = $request->input('search');
        $companyId = $request->input('company_id');
        $deptId    = $request->input('department_id');
        $empStatus = $request->input('emp_status');
        $status    = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('id_no', 'like', "%{$search}%");
        }));

        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when(filled($deptId), fn($q) => $q->where('department_id', $deptId));
        $query->when(filled($empStatus), fn($q) => $q->where('emp_status', $empStatus));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $query;
    }
}
