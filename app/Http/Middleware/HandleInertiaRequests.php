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
                'user'         => $user,
                'menus'        => $this->getMenus($user),
                'isSuperAdmin' => $user?->hasRole('super_admin') ?? false,
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

        // Admin roles get full admin menus
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('manager')) {
            return config('services.menus');
        }

        // Regular employees
        return config('services.emp-menus');
    }
}
