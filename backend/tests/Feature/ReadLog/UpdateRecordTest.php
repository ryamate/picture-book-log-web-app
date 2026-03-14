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

class UpdateRecordTest extends TestCase
{
    use RefreshDatabase;

    private function createFamilyWithRecord(): array
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $child1 = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);
        $child2 = Child::create(['family_id' => $family->id, 'name' => 'たろう', 'birthday' => '2022-06-01']);
        $book = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'read_status' => 'unread',
        ]);
        $tag = Tag::create(['name' => '寝る前']);

        $record = ReadRecord::create([
            'picture_book_id' => $book->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-10', 'memo' => '旧メモ',
        ]);
        $record->children()->attach($child1->id, ['reaction' => '大喜び']);
        $record->tags()->attach($tag->id);

        return [$family, $user, $child1, $child2, $book, $record, $tag];
    }

    public function test_member_can_update_record(): void
    {
        [$family, $user, $child1, $child2, $book, $record, $tag] = $this->createFamilyWithRecord();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/records/{$record->id}", [
                'read_date' => '2026-03-12',
                'memo' => '新メモ',
                'children' => [
                    ['child_id' => $child1->id, 'reaction' => 'まあまあ'],
                    ['child_id' => $child2->id, 'reaction' => 'にこにこ'],
                ],
                'tags' => ['寝る前', 'お気に入り'],
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('2026-03-12', $data['read_date']);
        $this->assertEquals('新メモ', $data['memo']);
        $this->assertCount(2, $data['children']);
        $this->assertCount(2, $data['tags']);
    }

    public function test_children_sync_correctly(): void
    {
        [$family, $user, $child1, $child2, $book, $record] = $this->createFamilyWithRecord();

        // child1 を削除し child2 を追加
        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/records/{$record->id}", [
                'read_date' => '2026-03-10',
                'children' => [
                    ['child_id' => $child2->id, 'reaction' => 'わくわく'],
                ],
            ]);

        $this->assertDatabaseMissing('child_read_record', [
            'read_record_id' => $record->id,
            'child_id' => $child1->id,
        ]);
        $this->assertDatabaseHas('child_read_record', [
            'read_record_id' => $record->id,
            'child_id' => $child2->id,
            'reaction' => 'わくわく',
        ]);
    }

    public function test_tags_sync_correctly(): void
    {
        [$family, $user, $child1, $child2, $book, $record, $tag] = $this->createFamilyWithRecord();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/records/{$record->id}", [
                'read_date' => '2026-03-10',
                'children' => [
                    ['child_id' => $child1->id],
                ],
                'tags' => ['新タグ'],
            ]);

        $this->assertDatabaseMissing('read_record_tag', [
            'read_record_id' => $record->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('tags', ['name' => '新タグ']);
    }

    public function test_non_member_cannot_update_record(): void
    {
        [$family, $user, $child1, $child2, $book, $record] = $this->createFamilyWithRecord();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/families/{$family->id}/records/{$record->id}", [
                'read_date' => '2026-03-10',
                'children' => [
                    ['child_id' => $child1->id],
                ],
            ]);

        $response->assertStatus(403);
    }
}
