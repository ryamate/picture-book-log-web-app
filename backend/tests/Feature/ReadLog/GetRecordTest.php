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

class GetRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_get_record_detail(): void
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
            'recorded_by' => $user->id, 'read_date' => '2026-03-10', 'memo' => 'メモ',
        ]);
        $record->children()->attach($child->id, ['reaction' => '大喜び']);
        $record->tags()->attach($tag->id);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records/{$record->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'picture_book', 'read_date', 'memo',
                    'children', 'tags', 'recorded_by', 'created_at',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('ぐりとぐら', $data['picture_book']['title']);
        $this->assertCount(1, $data['children']);
        $this->assertEquals('大喜び', $data['children'][0]['reaction']);
        $this->assertCount(1, $data['tags']);
        $this->assertEquals('寝る前', $data['tags'][0]['name']);
    }

    public function test_non_member_cannot_get_record(): void
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $book = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'read_status' => 'unread',
        ]);
        $child = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);

        $record = ReadRecord::create([
            'picture_book_id' => $book->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-10',
        ]);
        $record->children()->attach($child->id);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records/{$record->id}");

        $response->assertStatus(403);
    }
}
