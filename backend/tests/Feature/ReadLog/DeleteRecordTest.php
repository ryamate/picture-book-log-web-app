<?php

namespace Tests\Feature\ReadLog;

use App\Models\Child;
use App\Models\Family;
use App\Models\PictureBook;
use App\Models\ReadRecord;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_delete_record(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $child = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);
        $book = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'read_status' => 'unread',
        ]);

        $record = ReadRecord::create([
            'picture_book_id' => $book->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-10',
        ]);
        $record->children()->attach($child->id, ['reaction' => '大喜び']);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/families/{$family->id}/records/{$record->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('read_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('child_read_record', ['read_record_id' => $record->id]);
    }

    public function test_non_member_cannot_delete_record(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $child = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);
        $book = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'read_status' => 'unread',
        ]);

        $record = ReadRecord::create([
            'picture_book_id' => $book->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-10',
        ]);
        $record->children()->attach($child->id);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/v1/families/{$family->id}/records/{$record->id}");

        $response->assertStatus(403);
    }

    public function test_tag_master_remains_after_record_deletion(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $child = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);
        $book = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'read_status' => 'unread',
        ]);
        $tag = Tag::create(['name' => '寝る前']);

        $record = ReadRecord::create([
            'picture_book_id' => $book->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-10',
        ]);
        $record->children()->attach($child->id);
        $record->tags()->attach($tag->id);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/families/{$family->id}/records/{$record->id}");

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '寝る前']);
        $this->assertDatabaseMissing('read_record_tag', ['read_record_id' => $record->id]);
    }
}
