<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'     => 'System Admin',
            'email'    => 'sysadmin@mail.com',
            'password' => Hash::make('pass234'),
        ]);
        $admin->roles()->attach(Role::where('name', 'Super Admin')->first());

        $companyAdmin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@mail.com',
            'password' => Hash::make('pass234'),
        ]);
        $companyAdmin->roles()->attach(Role::where('name', 'Admin')->first());
    }
}
