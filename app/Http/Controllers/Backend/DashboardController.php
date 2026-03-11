<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Backend\DashboardService;
use App\Services\Backend\EmployeeDashboardService;
use App\Services\Backend\SharedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected readonly DashboardService $dashboardService,
        protected readonly EmployeeDashboardService $employeeDashboardService,
        protected readonly SharedService $shared
    ) {}

    public function __invoke()
    {
        $user = auth()->user();
        $user->load('roles');

        $adminRoles = ['super_admin', 'admin', 'hr', 'manager'];
        $hasAdminRole = $user->roles->whereIn('slug', $adminRoles)->isNotEmpty();

        // Employee → employee dashboard with data
        if ($user->isEmployee() && !$hasAdminRole) {
            $employee = $user->employee;
            $data = $this->employeeDashboardService->getData($employee);

            return Inertia::render('Backend/Dashboard/employee', array_merge($data, [
                'employeeName' => $employee->full_name,
                'designation'  => $employee->designation?->title ?? '-',
                'department'   => $employee->department?->name ?? '-',
            ]));
        }

        // Admin/HR/Manager/SuperAdmin → rich dashboard
        $companies = $this->shared->companies();
        $defaultCompanyId = $companies->first()?->id;

        return Inertia::render('Backend/Dashboard/admin', array_merge(
            $this->dashboardService->getData($defaultCompanyId),
            [
                'companies'        => $companies,
                'defaultCompanyId' => $defaultCompanyId,
            ]
        ));
    }

    public function getData(Request $request): JsonResponse
    {
        $companyId = $request->filled('company_id') ? $request->integer('company_id') : null;

        return response()->json($this->dashboardService->getData($companyId));
    }
}
