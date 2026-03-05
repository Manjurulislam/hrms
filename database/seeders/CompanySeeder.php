<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name'              => 'Softwindtech Ltd',
            'code'              => 'SWT001',
            'email'             => 'info@softwindtech.com',
            'phone'             => '+880-1712-345678',
            'address'           => '123 Gulshan Avenue, Dhaka-1212',
            'website'           => 'https://www.softwindtech.com',
            'office_start_time' => '09:00',
            'office_end_time'   => '18:00',
            'status'            => true,
        ]);
    }
}
