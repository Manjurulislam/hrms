<?php

namespace App\Services\Backend;

use App\Enums\Gender;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function getProfileData(User $user): array
    {
        $employee = $user->employee;

        return [
            'user'          => $user,
            'employee'      => $employee,
            'avatarUrl'     => $employee?->avatar_url,
            'genderOptions' => Gender::toOptions(),
        ];
    }

    public function updateProfile(User $user, array $data): void
    {
        $employee = $user->employee;

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
    }

    public function uploadAvatar(Employee $employee, $request): array
    {
        $employee->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        return [
            'message'   => 'Photo uploaded successfully.',
            'avatarUrl' => $employee->avatar_url,
        ];
    }

    public function removeAvatar(Employee $employee): void
    {
        $employee->clearMediaCollection('avatar');
    }

    public function changePassword(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);
    }
}
