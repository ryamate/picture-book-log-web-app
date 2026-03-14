<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListInvitationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::create(['name' => 'Test Family']);
        $this->user = User::factory()->create(['family_id' => $this->family->id]);
    }

    public function test_can_list_invitations(): void
    {
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'hanako@example.com',
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/families/{$this->family->id}/invitations");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'email', 'status', 'invited_by', 'expires_at', 'accepted_at', 'created_at'],
                ],
            ]);
    }

    public function test_status_is_correctly_computed(): void
    {
        // pending
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'pending@example.com',
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
        ]);

        // accepted
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'accepted@example.com',
            'token' => bin2hex(random_bytes(32)),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        // expired
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'expired@example.com',
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/families/{$this->family->id}/invitations");

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->json('data');
        $statuses = collect($data)->pluck('status', 'email')->all();

        $this->assertEquals('pending', $statuses['pending@example.com']);
        $this->assertEquals('accepted', $statuses['accepted@example.com']);
        $this->assertEquals('expired', $statuses['expired@example.com']);
    }

    public function test_non_member_cannot_list_invitations(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/families/{$this->family->id}/invitations");

        $response->assertStatus(403);
    }
}
