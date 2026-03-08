<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_update_family_name(): void
    {
        $family = Family::create(['name' => 'Old Name']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'New Name',
                ],
            ]);

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'name' => 'New Name',
        ]);
    }

    public function test_non_member_cannot_update_family(): void
    {
        $family = Family::create(['name' => 'Some Family']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_update_family_fails_with_empty_name(): void
    {
        $family = Family::create(['name' => 'Some Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}", [
                'name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
