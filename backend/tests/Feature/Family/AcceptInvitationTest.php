<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptInvitationTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::create(['name' => 'Test Family']);
        $inviter = User::factory()->create(['family_id' => $this->family->id]);

        $this->token = bin2hex(random_bytes(32));
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $inviter->id,
            'email' => 'invitee@example.com',
            'token' => $this->token,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_can_accept_invitation(): void
    {
        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invitations/{$this->token}/accept");

        $response->assertOk()
            ->assertJsonPath('message', '家族に参加しました。');

        $user->refresh();
        $this->assertEquals($this->family->id, $user->family_id);

        $this->assertDatabaseHas('family_invitations', [
            'token' => $this->token,
        ]);
        $this->assertNotNull(
            FamilyInvitation::where('token', $this->token)->first()->accepted_at
        );
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $expiredToken = bin2hex(random_bytes(32));
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => User::factory()->create(['family_id' => $this->family->id])->id,
            'email' => 'expired@example.com',
            'token' => $expiredToken,
            'expires_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['email' => 'expired@example.com']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invitations/{$expiredToken}/accept");

        $response->assertStatus(410);
    }

    public function test_cannot_accept_already_accepted_invitation(): void
    {
        $acceptedToken = bin2hex(random_bytes(32));
        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => User::factory()->create(['family_id' => $this->family->id])->id,
            'email' => 'accepted@example.com',
            'token' => $acceptedToken,
            'accepted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $user = User::factory()->create(['email' => 'accepted@example.com']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invitations/{$acceptedToken}/accept");

        $response->assertStatus(409);
    }

    public function test_cannot_accept_nonexistent_token(): void
    {
        $user = User::factory()->create();
        $fakeToken = bin2hex(random_bytes(32));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invitations/{$fakeToken}/accept");

        $response->assertStatus(500);
    }

    public function test_user_already_in_family_cannot_accept(): void
    {
        $otherFamily = Family::create(['name' => 'Other Family']);
        $user = User::factory()->create([
            'email' => 'already@example.com',
            'family_id' => $otherFamily->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/invitations/{$this->token}/accept");

        $response->assertStatus(409);
    }

    public function test_unauthenticated_user_cannot_accept(): void
    {
        $response = $this->postJson("/api/v1/invitations/{$this->token}/accept");

        $response->assertStatus(401);
    }
}
