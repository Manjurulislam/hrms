<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(): Response
    {
        $user     = auth()->user();
        $employee = $user->employee;

        return Inertia::render('Backend/Profile/index', [
            'user'          => $user,
            'employee'      => $employee,
            'avatarUrl'     => $employee?->avatar_url,
            'genderOptions' => Gender::toOptions(),
        ]);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user     = auth()->user();
        $employee = $user->employee;
        $data     = $request->validated();

        DB::transaction(function () use ($user, $employee, $data) {
            $user->update([
                'name'  => data_get($data, 'name'),
                'email' => data_get($data, 'email'),
            ]);

            if ($employee) {
                $employee->update([
                    'first_name'    => data_get($data, 'first_name'),
                    'last_name'     => data_get($data, 'last_name'),
                    'email'         => data_get($data, 'email'),
                    'phone'         => data_get($data, 'phone'),
                    'gender'        => data_get($data, 'gender'),
                    'date_of_birth' => data_get($data, 'date_of_birth'),
                    'address'       => data_get($data, 'address'),
                ]);
            }
        });

        return back()->with('success', 'Profile updated successfully.');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $employee->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        return response()->json([
            'message'   => 'Photo uploaded successfully.',
            'avatarUrl' => $employee->avatar_url,
        ]);
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $employee->clearMediaCollection('avatar');

        return response()->json(['message' => 'Photo removed successfully.']);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
