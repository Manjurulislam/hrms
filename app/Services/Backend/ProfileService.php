<?php

namespace App\Services\Backend;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\DesignationLevel;
use App\Services\Backend\SharedService;

class ProfileService
{
    public function getProfileData(User $user): array
    {
        $employee = $user->employee;
        $sharedService = app(SharedService::class);

        return [
            'user'                 => $user,
            'employee'             => $employee,
            'avatarUrl'            => $employee?->avatar_url,
            'genderOptions'        => Gender::toOptions(),
            'bloodGroupOptions'    => BloodGroup::toOptions(),
            'maritalStatusOptions' => MaritalStatus::toOptions(),
            'designations'         => $sharedService->designations(
                companyId: $employee?->company_id,
                excludeLevels: [DesignationLevel::TopExecutive->value],
            ),
        ];
    }

    public function updateProfile(User $user, array $data): void
    {
        $employee = $user->employee;

        DB::transaction(function () use ($user, $employee, $data) {
            $user->update([
                'name'  => trim(data_get($data, 'first_name') . ' ' . data_get($data, 'last_name')),
                'email' => data_get($data, 'email'),
            ]);

            if ($employee) {
                $employee->update([
                    'first_name'        => data_get($data, 'first_name'),
                    'last_name'         => data_get($data, 'last_name'),
                    'email'             => data_get($data, 'email'),
                    'phone'             => data_get($data, 'phone'),
                    'sec_phone'         => data_get($data, 'sec_phone'),
                    'nid'               => data_get($data, 'nid'),
                    'gender'            => data_get($data, 'gender'),
                    'date_of_birth'     => data_get($data, 'date_of_birth'),
                    'blood_group'       => data_get($data, 'blood_group'),
                    'marital_status'    => data_get($data, 'marital_status'),
                    'emergency_contact' => data_get($data, 'emergency_contact'),
                    'bank_account'      => data_get($data, 'bank_account'),
                    'address'           => data_get($data, 'address'),
                    'designation_id'    => data_get($data, 'designation_id'),
                ]);
            }
        });
    }

    public function uploadAvatar(Employee $employee, Request $request): array
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
            'password' => $password,
        ]);
    }
}
