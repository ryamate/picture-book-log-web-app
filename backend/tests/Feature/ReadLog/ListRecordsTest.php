<?php

namespace Tests\Feature\ReadLog;

use App\Models\Child;
use App\Models\Family;
use App\Models\PictureBook;
use App\Models\ReadRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function createFamilyWithRecords(): array
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $child1 = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);
        $child2 = Child::create(['family_id' => $family->id, 'name' => 'たろう', 'birthday' => '2022-06-01']);
        $book1 = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'ぐりとぐら', 'authors' => ['中川李枝子'], 'read_status' => 'unread',
        ]);
        $book2 = PictureBook::create([
            'family_id' => $family->id, 'registered_by' => $user->id,
            'title' => 'はらぺこあおむし', 'authors' => ['エリック・カール'], 'read_status' => 'unread',
        ]);

        $record1 = ReadRecord::create([
            'picture_book_id' => $book1->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-10',
        ]);
        $record1->children()->attach($child1->id, ['reaction' => '大喜び']);

        $record2 = ReadRecord::create([
            'picture_book_id' => $book2->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-12',
        ]);
        $record2->children()->attach([$child1->id, $child2->id]);

        $record3 = ReadRecord::create([
            'picture_book_id' => $book1->id, 'family_id' => $family->id,
            'recorded_by' => $user->id, 'read_date' => '2026-03-14',
        ]);
        $record3->children()->attach($child2->id, ['reaction' => 'にこにこ']);

        return [$family, $user, $child1, $child2, $book1, $book2];
    }

    public function test_member_can_list_records_with_pagination(): void
    {
        [$family, $user] = $this->createFamilyWithRecords();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'picture_book', 'read_date', 'children', 'tags']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_filter_by_child_id(): void
    {
        [$family, $user, $child1, $child2, $book1, $book2] = $this->createFamilyWithRecords();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records?child_id={$child2->id}");

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_filter_by_picture_book_id(): void
    {
        [$family, $user, $child1, $child2, $book1] = $this->createFamilyWithRecords();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records?picture_book_id={$book1->id}");

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_filter_by_date_range(): void
    {
        [$family, $user] = $this->createFamilyWithRecords();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records?date_from=2026-03-11&date_to=2026-03-13");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_non_member_cannot_list_records(): void
    {
        [$family] = $this->createFamilyWithRecords();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/families/{$family->id}/records");

        $response->assertStatus(403);
    }
}
