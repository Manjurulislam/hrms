<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        return [
            'title'        => $this->faker->sentence(4),
            'description'  => $this->faker->paragraph(),
            'company_id'   => Company::factory(),
            'created_by'   => User::factory(),
            'published_at' => now()->subDay(),
            'status'       => true,
        ];
    }
}
