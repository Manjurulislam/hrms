<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'id_no'         => $this->faker->unique()->numerify('EMP-#####'),
            'first_name'    => $this->faker->firstName(),
            'last_name'     => $this->faker->lastName(),
            'email'         => $this->faker->unique()->safeEmail(),
            'company_id'    => Company::factory(),
            // Keep the department in the same company as the employee.
            'department_id' => fn (array $attrs) => Department::factory()
                ->create(['company_id' => $attrs['company_id']])->id,
            'status'        => true,
        ];
    }
}
