<?php

namespace Tests\Feature\Family;

use App\Models\Family;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddChildTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_child(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/children", [
                'name' => 'Taro',
                'birthday' => '2020-01-15',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'birthday',
                ],
            ]);

        $this->assertDatabaseHas('children', [
            'family_id' => $family->id,
            'name' => 'Taro',
        ]);
    }

    public function test_add_child_with_future_birthday_fails(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/children", [
                'name' => 'Taro',
                'birthday' => $tomorrow,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birthday']);
    }

    public function test_non_member_cannot_add_child(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/children", [
                'name' => 'Taro',
                'birthday' => '2020-01-15',
            ]);

        $response->assertStatus(403);
    }
}
