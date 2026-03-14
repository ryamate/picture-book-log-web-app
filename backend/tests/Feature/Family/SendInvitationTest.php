<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Packages\Family\Infrastructure\Mail\InvitationMail;
use Tests\TestCase;

class SendInvitationTest extends TestCase
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

    public function test_can_send_invitation(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/families/{$this->family->id}/invitations", [
                'email' => 'hanako@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', '招待メールを送信しました。');

        $this->assertDatabaseHas('family_invitations', [
            'family_id' => $this->family->id,
            'email' => 'hanako@example.com',
            'invited_by' => $this->user->id,
        ]);

        Mail::assertSent(InvitationMail::class, function ($mail) {
            return $mail->hasTo('hanako@example.com');
        });
    }

    public function test_cannot_invite_self(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/families/{$this->family->id}/invitations", [
                'email' => $this->user->email,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Mail::assertNothingSent();
    }

    public function test_cannot_invite_existing_family_member(): void
    {
        Mail::fake();

        $member = User::factory()->create([
            'family_id' => $this->family->id,
            'email' => 'member@example.com',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/families/{$this->family->id}/invitations", [
                'email' => 'member@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Mail::assertNothingSent();
    }

    public function test_cannot_send_duplicate_invitation(): void
    {
        Mail::fake();

        FamilyInvitation::create([
            'family_id' => $this->family->id,
            'invited_by' => $this->user->id,
            'email' => 'hanako@example.com',
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/families/{$this->family->id}/invitations", [
                'email' => 'hanako@example.com',
            ]);

        $response->assertStatus(409);
    }

    public function test_non_member_cannot_send_invitation(): void
    {
        Mail::fake();

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/families/{$this->family->id}/invitations", [
                'email' => 'hanako@example.com',
            ]);

        $response->assertStatus(403);

        Mail::assertNothingSent();
    }

    public function test_can_invite_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/families/{$this->family->id}/invitations", [
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(201);

        Mail::assertSent(InvitationMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com');
        });
    }
}
