<?php

namespace Tests\Feature\Family;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveChildTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_remove_child(): void
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
            ->deleteJson("/api/v1/families/{$family->id}/children/{$child->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('children', [
            'id' => $child->id,
        ]);
    }

    public function test_non_member_cannot_remove_child(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $child = Child::create([
            'family_id' => $family->id,
            'name' => 'Taro',
            'birthday' => '2020-01-15',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/families/{$family->id}/children/{$child->id}");

        $response->assertStatus(403);
    }
}
