<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Secure/Users/Index');
    }

    public function store(UserRequest $request)
    {
        try {
            User::create(collect($request->validated())->filter()->toArray());
            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function create()
    {
        return Inertia::render('Backend/Secure/Users/Create');
    }

    public function get(Request $request)
    {
        $query  = User::query()
            ->with(['employee:id,first_name,last_name,email,department_id', 'employee.department:id,name'])
            ->orderBy('name');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function edit(User $user)
    {
        return Inertia::render('Backend/Secure/Users/Edit', [
            'item' => $user,
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        try {
            $user->fill(collect($request->validated())->filter()->toArray())->save();
            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
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
