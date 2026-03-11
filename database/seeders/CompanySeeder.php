<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name'            => 'Softwindtech Ltd',
            'code'            => 'SWT001',
            'email'           => 'info@softwindtech.com',
            'phone'           => '+880-1712-345678',
            'address'         => '123 Gulshan Avenue, Dhaka-1212',
            'website'         => 'https://www.softwindtech.com',
            'office_start'    => '09:00',
            'office_end'      => '18:00',
            'work_hours'      => 8,
            'half_day_hours'  => 4,
            'late_grace'      => 15,
            'early_grace'     => 15,
            'max_sessions'    => 10,
            'min_session_gap' => 2,
            'max_breaks'      => 5,
            'auto_close'      => true,
            'auto_close_at'   => '23:59',
            'track_ip'        => true,
            'track_location'  => true,
            'status'          => true,
        ]);
    }
}
