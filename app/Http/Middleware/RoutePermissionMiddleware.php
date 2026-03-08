<?php

namespace App\Http\Middleware;

use App\Services\Utility\RoutePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoutePermissionMiddleware
{
    public function __construct(
        protected readonly RoutePermissionService $permissionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!$this->permissionService->canAccess(auth()->user(), $request->route()?->getName())) {
            abort(403, 'Access denied. You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
