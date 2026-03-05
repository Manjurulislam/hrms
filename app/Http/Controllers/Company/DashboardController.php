<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\Backend\CompanyDashboardService;
use App\Traits\CompanyAuth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly CompanyDashboardService $dashboardService
    ) {}

    public function __invoke(): Response
    {
        $data = $this->dashboardService->getData($this->activeCompanyId());

        return Inertia::render('Company/Dashboard', $data);
    }
}
