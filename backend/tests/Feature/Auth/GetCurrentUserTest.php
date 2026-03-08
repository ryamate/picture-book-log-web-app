<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetCurrentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_current_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_current_user(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }
}
