<?php

namespace Tests\Feature\Bookshelf;

use App\Models\Family;
use App\Models\PictureBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBookTest extends TestCase
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

    public function test_member_can_update_book(): void
    {
        [$family, $user, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/books/{$book->id}", [
                'rating' => 5,
                'read_status' => 'read',
                'review' => '子どもが大好きな一冊',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.read_status', 'read')
            ->assertJsonPath('data.review', '子どもが大好きな一冊');
    }

    public function test_invalid_rating_returns_validation_error(): void
    {
        [$family, $user, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/books/{$book->id}", [
                'rating' => 6,
                'read_status' => 'read',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_invalid_read_status_returns_validation_error(): void
    {
        [$family, $user, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/books/{$book->id}", [
                'read_status' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['read_status']);
    }

    public function test_non_member_cannot_update_book(): void
    {
        [$family, , $book] = $this->createFamilyWithUserAndBook();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/books/{$book->id}", [
                'rating' => 5,
                'read_status' => 'read',
            ]);

        $response->assertStatus(403);
    }
}
