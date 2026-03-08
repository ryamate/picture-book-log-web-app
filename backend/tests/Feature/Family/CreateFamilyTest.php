<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_family(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/families', [
                'name' => 'Yamada Family',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                ],
            ]);

        $user->refresh();
        $this->assertNotNull($user->family_id);
        $this->assertDatabaseHas('families', [
            'name' => 'Yamada Family',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_family(): void
    {
        $response = $this->postJson('/api/v1/families', [
            'name' => 'Yamada Family',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_family_fails_with_empty_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/families', [
                'name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_already_in_family_cannot_create_family(): void
    {
        $family = Family::create(['name' => 'Existing Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/families', [
                'name' => 'New Family',
            ]);

        $response->assertStatus(403);
    }
}
