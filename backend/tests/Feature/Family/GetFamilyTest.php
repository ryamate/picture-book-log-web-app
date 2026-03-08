<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_get_family(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $family->id,
                    'name' => 'Yamada Family',
                ],
            ]);
    }

    public function test_non_member_cannot_get_family(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}");

        $response->assertStatus(403);
    }
}
