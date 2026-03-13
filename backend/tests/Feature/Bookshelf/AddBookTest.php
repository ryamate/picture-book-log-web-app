<?php

namespace Tests\Feature\Bookshelf;

use App\Models\Family;
use App\Models\PictureBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddBookTest extends TestCase
{
    use RefreshDatabase;

    private function createFamilyWithUser(): array
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);

        return [$family, $user];
    }

    public function test_member_can_add_book(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/books", [
                'google_books_id' => 'test_google_id',
                'isbn' => '9784834000825',
                'title' => 'ぐりとぐら',
                'authors' => ['中川李枝子'],
                'thumbnail_url' => 'https://example.com/thumb.jpg',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'google_books_id',
                    'title',
                    'authors',
                    'rating',
                    'read_status',
                    'registered_by',
                ],
            ]);

        $this->assertDatabaseHas('picture_books', [
            'family_id' => $family->id,
            'title' => 'ぐりとぐら',
            'google_books_id' => 'test_google_id',
        ]);
    }

    public function test_duplicate_google_books_id_returns_conflict(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $user->id,
            'google_books_id' => 'test_google_id',
            'title' => 'ぐりとぐら',
            'authors' => ['中川李枝子'],
            'read_status' => 'unread',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/books", [
                'google_books_id' => 'test_google_id',
                'title' => 'ぐりとぐら',
                'authors' => ['中川李枝子'],
            ]);

        $response->assertStatus(409);
    }

    public function test_required_fields_missing_returns_validation_error(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/books", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'authors']);
    }

    public function test_non_member_cannot_add_book(): void
    {
        $family = Family::create(['name' => 'Other Family']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/books", [
                'title' => 'ぐりとぐら',
                'authors' => ['中川李枝子'],
            ]);

        $response->assertStatus(403);
    }

    public function test_manual_add_without_google_books_id(): void
    {
        [$family, $user] = $this->createFamilyWithUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/books", [
                'title' => '手作り絵本',
                'authors' => ['パパ'],
            ]);

        $response->assertStatus(201);
    }
}
