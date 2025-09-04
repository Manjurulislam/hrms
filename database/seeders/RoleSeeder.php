<?php

namespace Database\Seeders;


use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Temporarily disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Role::truncate();

        $items = [
            [
                'name'        => 'Super Admin',
                'description' => 'Full system access',
                'status'      => false,
            ],
            [
                'name'        => 'Admin',
                'description' => 'Operational Admin',
                'status'      => true,
            ],
            [
                'name'        => 'Analysis',
                'description' => 'Operational',
                'status'      => true,
            ],
            [
                'name'        => 'Employee',
                'description' => 'Employee',
                'status'      => true,
            ],
        ];


        foreach ($items as $item) {
            Role::create([
                'name' => data_get($item, 'name'),
            ]);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
