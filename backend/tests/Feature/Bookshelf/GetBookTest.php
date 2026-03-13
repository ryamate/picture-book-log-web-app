<?php

namespace Tests\Feature\Bookshelf;

use App\Models\Family;
use App\Models\PictureBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetBookTest extends TestCase
{
    use RefreshDatabase;

    private function createFamilyWithUserAndBook(): array
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $book = PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $user->id,
            'title' => 'ぐりとぐら',
            'authors' => ['中川李枝子'],
            'read_status' => 'unread',
        ]);

        return [$family, $user, $book];
    }

    public function test_member_can_get_book_detail(): void
    {
        [$family, $user, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'authors',
                    'rating',
                    'read_status',
                    'review',
                    'registered_by',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.title', 'ぐりとぐら');
    }

    public function test_non_member_cannot_get_book(): void
    {
        [$family, , $book] = $this->createFamilyWithUserAndBook();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books/{$book->id}");

        $response->assertStatus(403);
    }

    public function test_nonexistent_book_returns_404(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/books/99999");

        $response->assertStatus(404);
    }
}
