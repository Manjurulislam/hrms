<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiInfraTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_route_without_token_returns_json_401(): void
    {
        $res = $this->getJson('/v1/auth/me');

        $res->assertStatus(401);
        $res->assertJson(['success' => false]);
    }
}
