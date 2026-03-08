<?php

namespace App\Services\Utility;

class MenuService
{
    public function getMenus($user): array
    {
        if (!$user) {
            return [];
        }

        $isAdmin    = $user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('hr');
        $isEmployee = $user->isEmployee();

        if ($isAdmin && $isEmployee) {
            return array_merge($this->adminMenus(), $this->employeeMenus($user));
        }

        if ($isAdmin) {
            return $this->adminMenus();
        }

        return $this->employeeMenus($user);
    }

    protected function adminMenus(): array
    {
        return config('services.menus', []);
    }

    protected function employeeMenus($user): array
    {
        $menus     = config('services.emp-menus', []);
        $isManager = $user->employee?->subordinates()->exists() ?? false;

        if (!$isManager) {
            $menus = array_map(function ($menu) {
                if (isset($menu['children'])) {
                    $menu['children'] = array_values(array_filter($menu['children'], function ($child) {
                        return ($child['to'] ?? '') !== 'emp-leave.approvals';
                    }));
                }
                return $menu;
            }, $menus);
        }

        return $menus;
    }
}
