<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class UserController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Secure/Users/Index');
    }

    /**
     * @throws Throwable
     */
    public function store(UserRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create($request->safe()->except('role'));
            $user->roles()->sync($request->get('role'));
            DB::commit();
            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();
            return redirect()->back();
        }
    }

    public function create()
    {
        return Inertia::render('Backend/Secure/Users/Create', [
            'roles' => $this->getRoles(),
        ]);
    }

    protected function getRoles()
    {
        return Role::select('id', 'name')
            ->whereNot('slug', 'employee')
            ->active()->get();
    }

    public function get(Request $request)
    {
        $query = User::query();
        $query->with('roles')
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('slug', ['super_admin', 'employee']);
            });

        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->transformUsers($query, $rows);
        return response()->json($result);
    }

    public function edit(User $user)
    {
        $selectedRole = $user->roles()->pluck('role_id')->toArray();
        return Inertia::render('Backend/Secure/Users/Edit', [
            'item'         => $user,
            'roles'        => $this->getRoles(),
            'selectedRole' => $selectedRole,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(UserRequest $request, User $user)
    {
        DB::beginTransaction();
        try {
            $user->update(collect($request->safe()->except('role'))->filter()->all());
            $user->roles()->sync($request->get('role'));
            DB::commit();
            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            DB::rollBack();
            return redirect()->back();
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(User $user)
    {
        return $this->toggleModelStatus($user);
    }
}
