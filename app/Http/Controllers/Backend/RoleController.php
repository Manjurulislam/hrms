<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Services\Backend\RoleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        protected readonly RoleService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Backend/Secure/Role/Index');
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Secure/Role/Create');
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('roles.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create role.']);
        }
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Backend/Secure/Role/Edit', $this->service->formData($role));
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $this->service->update($role, $request->validated());

            return to_route('roles.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update role.']);
        }
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $this->service->delete($role);

            return to_route('roles.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete role.']);
        }
    }

    public function toggleStatus(Role $role): JsonResponse
    {
        try {
            $status = $this->service->toggle($role);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
