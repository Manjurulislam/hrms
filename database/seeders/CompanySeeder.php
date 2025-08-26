<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::truncate();

        $companies = [
            [
                'name'    => 'Softwind Tech Ltd',
                'code'    => 'TSL001',
                'email'   => 'info@techsolutions.com',
                'phone'   => '+880-1712-345678',
                'address' => '123 Gulshan Avenue, Dhaka-1212',
                'website' => 'https://www.techsolutions.com',
                'status'  => true,
            ],
            [
                'name'    => 'Aliendie',
                'code'    => 'DMP002',
                'email'   => 'contact@digitalmarketingpro.com',
                'phone'   => '+880-1798-765432',
                'address' => '456 Dhanmondi Road, Dhaka-1205',
                'website' => 'https://www.digitalmarketingpro.com',
                'status'  => true,
            ],

        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
