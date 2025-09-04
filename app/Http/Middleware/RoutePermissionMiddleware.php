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

        $menus = $user->isEmployee()
            ? config('services.emp-menus', []) // Add default empty array
            : config('services.menus', []);   // Add default empty array


        $allowedRoutes = collect($menus)
            ->flatMap(fn($menu) => $this->extractRoutes($menu));

        // Direct route match
        if ($allowedRoutes->contains($route)) {
            return true;
        }

        // Check for resource route patterns (users.index allows all users.*)
        return $this->hasResourceAccess($route, $allowedRoutes);
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
        // Extract the resource name from the route (e.g., 'users' from 'users.create')
        if (!str_contains($route, '.')) {
            return false;
        }

        [$resource] = explode('.', $route, 2);

        // Common resource routes that should be allowed if base route exists
        $resourceRoutes = [
            "{$resource}.index",
            "{$resource}.get",
            "{$resource}.create",
            "{$resource}.store",
            "{$resource}.show",
            "{$resource}.edit",
            "{$resource}.update",
            "{$resource}.destroy"
        ];
        // If any of the resource routes are in allowed routes, allow all
        return $allowedRoutes->intersect($resourceRoutes)->isNotEmpty();
    }
}
