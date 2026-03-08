<?php

namespace Tests\Feature\Family;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateChildTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_update_child(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $child = Child::create([
            'family_id' => $family->id,
            'name' => 'Taro',
            'birthday' => '2020-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/children/{$child->id}", [
                'name' => 'Jiro',
                'birthday' => '2021-06-01',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Jiro',
                ],
            ]);

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => 'Jiro',
        ]);
    }

    public function test_non_member_cannot_update_child(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $child = Child::create([
            'family_id' => $family->id,
            'name' => 'Taro',
            'birthday' => '2020-01-15',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/children/{$child->id}", [
                'name' => 'Jiro',
                'birthday' => '2021-06-01',
            ]);

        $response->assertStatus(403);
    }
}
