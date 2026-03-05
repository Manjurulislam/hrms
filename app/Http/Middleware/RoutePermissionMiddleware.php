<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoutePermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Add authentication check
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user  = auth()->user();
        $route = $request->route()?->getName(); // Add null safety


        if (!$this->userCanAccessRoute($user, $route)) {
            abort(403, 'Access denied. You do not have permission to access this resource.');
        }

        return $next($request);
    }

    private function userCanAccessRoute($user, ?string $route): bool
    {
        if (blank($route)) {
            return false;
        }

        // Optional: Skip certain routes
        if ($this->shouldSkipPermissionCheck($route)) {
            return true;
        }

        $menus = $this->getMenusForUser($user);


        $allowedRoutes = collect($menus)
            ->flatMap(fn($menu) => $this->extractRoutes($menu));

        // Direct route match
        if ($allowedRoutes->contains($route)) {
            return true;
        }

        // Check for resource route patterns (users.index allows all users.*)
        return $this->hasResourceAccess($route, $allowedRoutes);
    }

    private function shouldSkipPermissionCheck(string $route): bool
    {
        $skipRoutes = [
            'login',
            'logout',
            'dashboard',
            'company.dashboard',
            'company.switch',
            'attendance-records.get', // API for attendance data table
            'attendance-records.export', // API for attendance export
            'emp-attendance.start-work', // API for attendance check-in
            'emp-attendance.end-work', // API for attendance check-out
            'emp-attendance.start-break', // API for break start
            'emp-attendance.end-break', // API for break end
            'emp-attendance.current-status', // API for current status
            'emp-attendance.monthly-data', // API for monthly data
            'company.attendance.get', // API for company attendance data
            'company.attendance.export', // API for company attendance export
            'company.attendance.records', // API for employee attendance records
            'emp-leave.get', // API for employee leave data
            'company.leave-requests.get', // API for company leave requests data
        ];

        // Also skip any routes starting with 'api.'
        if (str_starts_with($route, 'api.')) {
            return true;
        }

        return in_array($route, $skipRoutes);
    }

    private function extractRoutes(array $menu): array
    {
        $routes = [];

        // Add main menu route
        if (filled($menu['to'] ?? null)) {
            $routes[] = $menu['to'];
        }

        // Add children routes
        if (isset($menu['children'])) {
            foreach ($menu['children'] as $child) {
                if (filled($child['to'] ?? null)) {
                    $routes[] = $child['to'];
                }
            }
        }
        return $routes;
    }

    private function hasResourceAccess(string $route, $allowedRoutes): bool
    {
        if (!str_contains($route, '.')) {
            return false;
        }

        // Handle nested resource routes like 'company.departments.create'
        $parts = explode('.', $route);

        if (count($parts) >= 3) {
            // e.g., company.departments.create → prefix = company.departments
            $prefix = implode('.', array_slice($parts, 0, -1));
            $resourceRoutes = collect([
                "{$prefix}.index", "{$prefix}.get", "{$prefix}.create",
                "{$prefix}.store", "{$prefix}.show", "{$prefix}.edit",
                "{$prefix}.update", "{$prefix}.destroy", "{$prefix}.toggle-status",
            ]);

            if ($allowedRoutes->intersect($resourceRoutes)->isNotEmpty()) {
                return true;
            }
        }

        // Standard resource routes (e.g., users.create)
        [$resource] = explode('.', $route, 2);
        $resourceRoutes = collect([
            "{$resource}.index", "{$resource}.get", "{$resource}.create",
            "{$resource}.store", "{$resource}.show", "{$resource}.edit",
            "{$resource}.update", "{$resource}.destroy", "{$resource}.toggle-status",
        ]);

        return $allowedRoutes->intersect($resourceRoutes)->isNotEmpty();
    }

    private function getMenusForUser($user): array
    {
        if ($user->hasRole('super_admin')) {
            return config('services.menus', []);
        }

        if ($user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('manager')) {
            return config('services.company-menus', []);
        }

        return config('services.emp-menus', []);
    }
}
