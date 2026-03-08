<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_family_members(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $otherMember = User::factory()->create();
        $otherMember->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/members");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_non_member_cannot_list_family_members(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/members");

        $response->assertStatus(403);
    }
}
