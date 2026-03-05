<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Backend\EmployeeDashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected readonly EmployeeDashboardService $employeeDashboardService
    ) {}

    public function __invoke()
    {
        $user = auth()->user();

        // Company Admin/HR/Manager → redirect to company dashboard
        if (!$user->hasRole('super_admin') && ($user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('manager'))) {
            return to_route('company.dashboard');
        }

        // Employee → employee dashboard with data
        if ($user->isEmployee() && !$user->hasRole('super_admin')) {
            $employee = $user->employee;
            $data = $this->employeeDashboardService->getData($employee);

            return Inertia::render('Backend/Dashboard/employee', array_merge($data, [
                'employeeName' => $employee->full_name,
                'designation'  => $employee->designation?->title ?? '-',
                'department'   => $employee->department?->name ?? '-',
            ]));
        }

        return Inertia::render('Backend/Dashboard/admin');
    }
}
