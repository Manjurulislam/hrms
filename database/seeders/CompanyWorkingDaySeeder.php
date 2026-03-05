<?php

namespace Database\Seeders;

use App\Models\CompanyWorkingDay;
use Illuminate\Database\Seeder;

class CompanyWorkingDaySeeder extends Seeder
{
    private const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function run(): void
    {
        // Softwindtech Ltd: Sun-Thu working, Fri-Sat off
        foreach (range(0, 6) as $day) {
            CompanyWorkingDay::create([
                'company_id'  => 1,
                'day_of_week' => $day,
                'day_label'   => self::DAY_LABELS[$day],
                'is_working'  => !in_array($day, [5, 6]), // Fri=5, Sat=6 off
            ]);
        }
    }
}
