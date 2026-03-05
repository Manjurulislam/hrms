<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Bangladesh government & religious holidays for 2026
        $bdHolidays = [
            // National holidays
            ['name' => 'International Mother Language Day',  'description' => 'Shaheed Dibosh - tribute to language martyrs of 1952',       'start_date' => '2026-02-21', 'end_date' => '2026-02-21'],
            ['name' => 'Birthday of Sheikh Mujibur Rahman', 'description' => 'National Children Day - Father of the Nation',                'start_date' => '2026-03-17', 'end_date' => '2026-03-17'],
            ['name' => 'Independence Day',                  'description' => 'Shadhinota Dibosh - declaration of independence in 1971',     'start_date' => '2026-03-26', 'end_date' => '2026-03-26'],
            ['name' => 'Bengali New Year (Pohela Boishakh)','description' => 'Bangla Noboborsho - first day of Bengali calendar year 1433', 'start_date' => '2026-04-14', 'end_date' => '2026-04-14'],
            ['name' => 'May Day',                           'description' => 'International Workers Day',                                   'start_date' => '2026-05-01', 'end_date' => '2026-05-01'],
            ['name' => 'National Mourning Day',             'description' => 'Assassination of Sheikh Mujibur Rahman in 1975',              'start_date' => '2026-08-15', 'end_date' => '2026-08-15'],
            ['name' => 'Victory Day',                       'description' => 'Bijoy Dibosh - victory in the Liberation War of 1971',        'start_date' => '2026-12-16', 'end_date' => '2026-12-16'],

            // Islamic holidays (approximate dates for 2026)
            ['name' => 'Shab-e-Meraj',                      'description' => 'Night of Ascension of Prophet Muhammad (PBUH)',               'start_date' => '2026-01-06', 'end_date' => '2026-01-06'],
            ['name' => 'Shab-e-Barat',                      'description' => 'Night of fortune and forgiveness',                           'start_date' => '2026-02-04', 'end_date' => '2026-02-04'],
            ['name' => 'Eid ul-Fitr',                       'description' => 'End of Ramadan - festival of breaking the fast',              'start_date' => '2026-03-21', 'end_date' => '2026-03-23'],
            ['name' => 'Eid ul-Adha',                       'description' => 'Festival of Sacrifice - Qurbani Eid',                        'start_date' => '2026-05-28', 'end_date' => '2026-05-30'],
            ['name' => 'Ashura',                             'description' => '10th of Muharram',                                           'start_date' => '2026-07-06', 'end_date' => '2026-07-06'],
            ['name' => 'Eid-e-Milad-un-Nabi',              'description' => 'Birthday of Prophet Muhammad (PBUH)',                          'start_date' => '2026-09-07', 'end_date' => '2026-09-07'],

            // Hindu holidays
            ['name' => 'Janmashtami',                        'description' => 'Birth anniversary of Lord Krishna',                          'start_date' => '2026-08-25', 'end_date' => '2026-08-25'],
            ['name' => 'Durga Puja (Bijoya Dashami)',       'description' => 'Major Hindu festival celebrating Goddess Durga',               'start_date' => '2026-10-02', 'end_date' => '2026-10-02'],

            // Buddhist holiday
            ['name' => 'Buddha Purnima',                     'description' => 'Birth, enlightenment and death of Gautama Buddha',            'start_date' => '2026-05-12', 'end_date' => '2026-05-12'],

            // Christian holiday
            ['name' => 'Christmas Day',                      'description' => 'Birth of Jesus Christ',                                      'start_date' => '2026-12-25', 'end_date' => '2026-12-25'],
        ];

        foreach ($bdHolidays as $holiday) {
            Holiday::create(array_merge($holiday, [
                'company_id' => 1,
                'status'     => true,
            ]));
        }
    }
}
