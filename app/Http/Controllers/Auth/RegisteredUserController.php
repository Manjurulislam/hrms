<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmployeeRegisterRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'companies'     => Company::where('status', true)->select('id', 'name')->get(),
            'genderOptions' => Gender::toOptions(),
        ]);
    }

    public function store(EmployeeRegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            $employee = Employee::create([
                'first_name'    => $data['first_name'],
                'email'         => $data['email'],
                'gender'        => $data['gender'] ?? null,
                'company_id'    => $data['company_id'],
                'department_id' => Company::find($data['company_id'])
                    ->departments()->first()?->id ?? 1,
                'emp_status'    => 'confirmed',
            ]);

            $user = User::create([
                'name'        => $employee->full_name,
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'employee_id' => $employee->id,
            ]);

            $employeeRole = Role::where('slug', 'employee')->first();
            if ($employeeRole) {
                $user->roles()->attach($employeeRole);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
