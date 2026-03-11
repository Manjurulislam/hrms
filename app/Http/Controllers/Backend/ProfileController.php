<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Backend\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected readonly ProfileService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Backend/Profile/index', $this->service->getProfileData(auth()->user()));
    }

    public function update(ProfileUpdateRequest $request)
    {
        try {
            $this->service->updateProfile($request->user(), $request->validated());
            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update profile.']);
        }
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

        try {
            return response()->json($this->service->uploadAvatar($employee, $request));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to upload photo.'], 500);
        }
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        try {
            $this->service->removeAvatar($employee);
            return response()->json(['message' => 'Photo removed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to remove photo.'], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $this->service->changePassword($request->user(), $request->validated('password'));
            return back()->with('success', 'Password changed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to change password.']);
        }
    }
}
