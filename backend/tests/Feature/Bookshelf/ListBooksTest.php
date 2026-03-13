<?php

namespace Tests\Feature\Bookshelf;

use App\Models\Family;
use App\Models\PictureBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListBooksTest extends TestCase
{
    use RefreshDatabase;

    private function createFamilyWithUser(): array
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        return [$family, $user];
    }

    public function test_member_can_list_books(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $user->id,
            'title' => 'ぐりとぐら',
            'authors' => ['中川李枝子'],
            'read_status' => 'unread',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'authors', 'read_status'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_filter_by_read_status(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $user->id,
            'title' => 'Book A',
            'authors' => ['Author'],
            'read_status' => 'read',
        ]);

        PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $user->id,
            'title' => 'Book B',
            'authors' => ['Author'],
            'read_status' => 'unread',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books?status=read");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Book A');
    }

    public function test_non_member_cannot_list_books(): void
    {
        $family = Family::create(['name' => 'Other Family']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books");

        $response->assertStatus(403);
    }

    public function test_empty_bookshelf(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);
    }
}
