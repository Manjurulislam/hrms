<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->randomElement(['Casual', 'Sick', 'Annual']),
            'max_per_year' => 12,
            'company_id'   => Company::factory(),
            'status'       => true,
        ];
    }
}
