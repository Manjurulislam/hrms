<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'description' => 'Full system access'],
            ['name' => 'Admin',       'description' => 'Company admin access'],
            ['name' => 'HR',          'description' => 'Human resources management'],
            ['name' => 'Manager',     'description' => 'Team/department manager'],
            ['name' => 'Employee',    'description' => 'Regular employee access'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

    }
}
