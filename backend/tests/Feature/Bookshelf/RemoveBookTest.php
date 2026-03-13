<?php

namespace Tests\Feature\Bookshelf;

use App\Models\Family;
use App\Models\PictureBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_remove_book(): void
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

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/families/{$family->id}/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('picture_books', ['id' => $book->id]);
    }

    public function test_non_member_cannot_remove_book(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $owner = User::factory()->create();
        $owner->update(['family_id' => $family->id]);

        $book = PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $owner->id,
            'title' => 'ぐりとぐら',
            'authors' => ['中川李枝子'],
            'read_status' => 'unread',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/v1/families/{$family->id}/books/{$book->id}");

        $response->assertStatus(403);
    }
}
