<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name'              => 'Softwind Tech',
                'code'              => 'SWT001',
                'email'             => 'info@softwindtech.com',
                'phone'             => '+880-1712-345678',
                'address'           => '123 Gulshan Avenue, Dhaka-1212',
                'website'           => 'https://www.softwindtech.com',
                'office_start_time' => '09:00',
                'office_end_time'   => '18:00',
                'status'            => true,
            ],
            [
                'name'              => 'Aliede',
                'code'              => 'ALD002',
                'email'             => 'contact@aliede.com',
                'phone'             => '+880-1798-765432',
                'address'           => '456 Dhanmondi Road, Dhaka-1205',
                'website'           => 'https://www.aliede.com',
                'office_start_time' => '10:00',
                'office_end_time'   => '19:00',
                'status'            => true,
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
