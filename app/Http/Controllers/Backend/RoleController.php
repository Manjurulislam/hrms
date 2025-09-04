<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RoleController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Secure/Role/Index');
    }


    public function store(RoleRequest $request)
    {
        try {
            Role::create($request->validated());
            return to_route('roles.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function create()
    {
        return Inertia::render('Backend/Secure/Role/Create');
    }

    public function edit(Role $role)
    {
        return Inertia::render('Backend/Secure/Role/Edit', [
            'item' => $role,
        ]);
    }


    public function update(RoleRequest $request, Role $role)
    {

        try {
            $role->update($request->validated());
            return to_route('roles.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function get(Request $request)
    {
        $query  = Role::query();
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function destroy(Role $role)
    {
        try {
            $role->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Role $role)
    {
        return $this->toggleModelStatus($role);
    }
}
