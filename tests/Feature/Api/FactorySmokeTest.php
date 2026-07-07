<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_and_user_link(): void
    {
        $employee = Employee::factory()->create();
        $user     = User::factory()->create(['employee_id' => $employee->id]);

        $this->assertSame($employee->id, $user->employee->id);
    }
}
