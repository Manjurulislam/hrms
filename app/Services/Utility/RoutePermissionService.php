<?php

namespace App\Services\Utility;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoutePermissionService
{
    private const SKIP_ROUTES = [
        'login',
        'logout',
        'dashboard',
        'dashboard.data',
        'profile',
        'profile.update',
        'profile.password',
    ];

    private const SKIP_PREFIXES = [
        'api.',
        'emp-attendance.',
        'emp-leave.',
        'emp-notices.',
        'attendance-records.',
    ];

    private const RESOURCE_ACTIONS = [
        'index', 'get', 'create', 'store',
        'show', 'edit', 'update', 'destroy',
        'toggle-status', 'export',
    ];

    public function canAccess($user, ?string $route): bool
    {
        if (blank($route)) {
            return false;
        }

        if ($this->shouldSkip($route)) {
            return true;
        }

        $allowedRoutes = $this->getAllowedRoutes($user);

        return $allowedRoutes->contains($route) || $this->hasResourceAccess($route, $allowedRoutes);
    }

    protected function shouldSkip(string $route): bool
    {
        return in_array($route, self::SKIP_ROUTES)
            || Str::startsWith($route, self::SKIP_PREFIXES);
    }

    protected function getAllowedRoutes($user): Collection
    {
        return collect(app(MenuService::class)->getMenus($user))
            ->flatMap(fn(array $menu) => $this->extractRoutes($menu));
    }

    protected function extractRoutes(array $menu): array
    {
        $routes = [];

        if (filled(data_get($menu, 'to'))) {
            $routes[] = $menu['to'];
        }

        foreach (data_get($menu, 'children', []) as $child) {
            if (filled(data_get($child, 'to'))) {
                $routes[] = $child['to'];
            }
        }

        return $routes;
    }

    protected function hasResourceAccess(string $route, Collection $allowedRoutes): bool
    {
        if (!Str::contains($route, '.')) {
            return false;
        }

        $resource = Str::before($route, '.');

        $resourceRoutes = collect(self::RESOURCE_ACTIONS)
            ->map(fn(string $action) => "{$resource}.{$action}");

        return $allowedRoutes->intersect($resourceRoutes)->isNotEmpty();
    }
}
