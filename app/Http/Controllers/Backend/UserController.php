<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\Backend\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected readonly UserService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Backend/Secure/Users/Index', [
            'roles' => $this->service->formData()['roles'],
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Secure/Users/Create', $this->service->formData());
    }

    public function store(UserRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create user.']);
        }
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Backend/Secure/Users/Edit', $this->service->formData($user));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->service->update($user, $request->validated());

            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update user.']);
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->service->delete($user);

            return to_route('users.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete user.']);
        }
    }

    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $status = $this->service->toggle($user);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
