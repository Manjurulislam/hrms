<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Holiday::truncate();

        $holidays = [
            [
                'name'        => 'Independence Day',
                'description' => 'National holiday celebrating the independence of Bangladesh on March 26, 1971.',
                'day_at'      => '2025-03-26',
                'company_id'  => 1, // Tech Solutions Ltd.
                'status'      => true,
            ],
            [
                'name'        => 'Victory Day',
                'description' => 'National holiday commemorating the victory in the Bangladesh Liberation War on December 16, 1971.',
                'day_at'      => '2025-12-16',
                'company_id'  => 2, // Digital Marketing Pro
                'status'      => true,
            ],
            [
                'name'        => 'International Mother Language Day',
                'description' => 'Public holiday honoring the martyrs of the Bengali Language Movement and celebrating linguistic diversity.',
                'day_at'      => '2025-02-21',
                'company_id'  => 3, // Green Energy Systems
                'status'      => false,
            ],
            [
                'name'        => 'Eid ul-Fitr',
                'description' => 'Religious festival marking the end of Ramadan, celebrated with family gatherings and feasts.',
                'day_at'      => '2025-03-30',
                'company_id'  => 4, // Healthcare Innovations
                'status'      => true,
            ],
            [
                'name'        => 'Durga Puja',
                'description' => 'Major Hindu festival celebrating Goddess Durga, observed with cultural programs and community celebrations.',
                'day_at'      => '2025-09-28',
                'company_id'  => 5, // Financial Services Group
                'status'      => true,
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }
}
