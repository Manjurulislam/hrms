<?php

namespace Database\Seeders;

use App\Models\CompanyWorkingDay;
use Illuminate\Database\Seeder;

class CompanyWorkingDaySeeder extends Seeder
{
    public function run(): void
    {
        // Softwind Tech: Sun-Thu working, Fri-Sat off
        foreach (range(0, 6) as $day) {
            CompanyWorkingDay::create([
                'company_id'  => 1,
                'day_of_week' => $day,
                'is_working'  => !in_array($day, [5, 6]), // Fri=5, Sat=6 off
            ]);
        }

        // Aliede: Mon-Fri working, Sat-Sun off
        foreach (range(0, 6) as $day) {
            CompanyWorkingDay::create([
                'company_id'  => 2,
                'day_of_week' => $day,
                'is_working'  => !in_array($day, [0, 6]), // Sun=0, Sat=6 off
            ]);
        }
    }
}
