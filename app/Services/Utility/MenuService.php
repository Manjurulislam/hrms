<?php

namespace App\Services\Utility;

use Illuminate\Support\Facades\Cache;

class MenuService
{
    // Cache key for a user's resolved menu tree.
    public static function cacheKey(int $userId): string
    {
        return "menus.user.{$userId}";
    }

    // Drop a user's cached menu (call after a role / manager change).
    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    public function getMenus($user): array
    {
        if (!$user) {
            return [];
        }

        // The menu tree is a pure function of the user's access flags (which otherwise
        // cost a roles load + a subordinates() query on every request). Cache it.
        return Cache::remember(self::cacheKey($user->id), now()->addHour(), fn() => $this->build($user));
    }

    protected function build($user): array
    {
        $user->loadMissing('roles');
        $roleSlugs  = $user->roles->pluck('slug');
        $isAdmin    = $roleSlugs->intersect(['super_admin', 'admin', 'hr'])->isNotEmpty();
        $isEmployee = $user->isEmployee();
        $isManager  = $isEmployee && ($user->employee?->subordinates()->exists() ?? false);

        $allMenus = config('services.menus', []);

        return array_values(array_filter(
            array_map(fn($menu) => $this->filterMenu($menu, $isAdmin, $isEmployee, $isManager), $allMenus),
            fn($menu) => $menu !== null
        ));
    }

    protected function filterMenu(array $menu, bool $isAdmin, bool $isEmployee, bool $isManager): ?array
    {
        $access = $menu['access'] ?? 'all';

        if (!$this->hasAccess($access, $isAdmin, $isEmployee, $isManager)) {
            return null;
        }

        // Filter children if present
        if (isset($menu['children'])) {
            $menu['children'] = array_values(array_filter(
                $menu['children'],
                fn($child) => $this->hasAccess($child['access'] ?? 'all', $isAdmin, $isEmployee, $isManager)
            ));

            if (empty($menu['children'])) {
                return null;
            }
        }

        return $menu;
    }

    protected function hasAccess(string $access, bool $isAdmin, bool $isEmployee, bool $isManager): bool
    {
        return match ($access) {
            'all'      => true,
            'admin'    => $isAdmin,
            'employee' => $isEmployee,
            'manager'  => $isManager,
            default    => false,
        };
    }
}
