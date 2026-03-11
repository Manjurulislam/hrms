<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmployeeRegisterRequest;
use App\Services\Auth\RegisterService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected readonly RegisterService $service
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register', $this->service->formData());
    }

    public function store(EmployeeRegisterRequest $request): RedirectResponse
    {
        try {
            $this->service->register($request->validated());

            return redirect(route('login'))->with('status', 'Registration successful. Please wait for admin approval.');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }
}
