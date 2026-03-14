<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelInvitationTest extends TestCase
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

    public function test_can_cancel_invitation(): void
    {
        $invitation = FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'hanako@example.com',
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/families/{$this->family->id}/invitations/{$invitation->id}");

        $response->assertOk()
            ->assertJsonPath('message', '招待をキャンセルしました。');

        $this->assertDatabaseMissing('family_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_cannot_cancel_accepted_invitation(): void
    {
        $invitation = FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'hanako@example.com',
            'token' => bin2hex(random_bytes(32)),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/families/{$this->family->id}/invitations/{$invitation->id}");

        $response->assertStatus(409);
    }

    public function test_non_member_cannot_cancel_invitation(): void
    {
        $invitation = FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'hanako@example.com',
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/v1/families/{$this->family->id}/invitations/{$invitation->id}");

        $response->assertStatus(403);
    }
}
