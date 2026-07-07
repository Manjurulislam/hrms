<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'title'         => $this->faker->sentence(3),
            'notes'         => $this->faker->sentence(),
            'total_days'    => 1,
            'company_id'    => Company::factory(),
            'employee_id'   => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'status'        => 'pending',
            'started_at'    => now()->addDay()->toDateString(),
            'ended_at'      => now()->addDay()->toDateString(),
        ];
    }
}
