<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth'  => [
                'user'             => $user,
                'menus'            => $this->getMenus($user),
                'currentCompany'   => $this->getCurrentCompany($user),
                'managedCompanies' => $this->getManagedCompanies($user),
                'isSuperAdmin'     => $user?->hasRole('super_admin') ?? false,
            ],
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    protected function getMenus($user): array
    {
        if (!$user) {
            return [];
        }

        // Super Admin gets full admin menus (all companies, users, roles)
        if ($user->hasRole('super_admin')) {
            return config('services.menus');
        }

        // Company Admin, HR, Manager get company-scoped menus
        if ($user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('manager')) {
            return config('services.company-menus');
        }

        // Regular employees
        return config('services.emp-menus');
    }

    protected function getCurrentCompany($user): ?array
    {
        if (!$user || !$user->employee) {
            return null;
        }

        $companyId = session('active_company_id', $user->employee->company_id);
        $company = \App\Models\Company::select('id', 'name')->find($companyId);

        return $company?->toArray();
    }

    protected function getManagedCompanies($user): array
    {
        if (!$user || !$user->employee) {
            return [];
        }

        $managed = $user->employee->managedCompanies()->select('companies.id', 'companies.name')->get();
        $primary = $user->employee->company()->select('id', 'name')->first();

        $all = collect([$primary])->merge($managed)->unique('id')->values();

        return $all->toArray();
    }
}
