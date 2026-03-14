<?php

namespace Tests\Feature\ReadLog;

use App\Models\Child;
use App\Models\Family;
use App\Models\PictureBook;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRecordTest extends TestCase
{
    use RefreshDatabase;

    private function createFamilyWithUserAndBook(): array
    {
        $family = Family::create(['name' => 'Yamada Family']);
        $user = User::factory()->create();
        $user->update(['family_id' => $family->id]);
        $child = Child::create(['family_id' => $family->id, 'name' => 'はなこ', 'birthday' => '2020-01-01']);
        $book = PictureBook::create([
            'family_id' => $family->id,
            'registered_by' => $user->id,
            'title' => 'ぐりとぐら',
            'authors' => ['中川李枝子'],
            'read_status' => 'unread',
        ]);

        return [$family, $user, $child, $book];
    }

    public function test_member_can_create_record_with_single_child(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->format('Y-m-d'),
                'children' => [
                    ['child_id' => $child->id],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'picture_book',
                    'read_date',
                    'children',
                    'tags',
                    'recorded_by',
                ],
            ]);

        $this->assertDatabaseHas('read_records', [
            'family_id' => $family->id,
            'picture_book_id' => $book->id,
        ]);
    }

    public function test_member_can_create_record_with_multiple_children_and_reactions(): void
    {
        [$family, $user, $child1, $book] = $this->createFamilyWithUserAndBook();
        $child2 = Child::create(['family_id' => $family->id, 'name' => 'たろう', 'birthday' => '2022-06-01']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->format('Y-m-d'),
                'memo' => '寝る前に読んだ',
                'children' => [
                    ['child_id' => $child1->id, 'reaction' => '大喜び'],
                    ['child_id' => $child2->id, 'reaction' => '途中で寝た'],
                ],
            ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertCount(2, $data['children']);

        $this->assertDatabaseHas('child_read_record', [
            'child_id' => $child1->id,
            'reaction' => '大喜び',
        ]);
        $this->assertDatabaseHas('child_read_record', [
            'child_id' => $child2->id,
            'reaction' => '途中で寝た',
        ]);
    }

    public function test_member_can_create_record_with_new_and_existing_tags(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();
        Tag::create(['name' => '寝る前']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->format('Y-m-d'),
                'children' => [
                    ['child_id' => $child->id],
                ],
                'tags' => ['寝る前', 'お気に入り'],
            ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertCount(2, $data['tags']);
        $this->assertDatabaseHas('tags', ['name' => 'お気に入り']);
    }

    public function test_empty_children_returns_validation_error(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->format('Y-m-d'),
                'children' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_other_family_book_returns_validation_error(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();

        $otherFamily = Family::create(['name' => 'Other Family']);
        $otherBook = PictureBook::create([
            'family_id' => $otherFamily->id,
            'registered_by' => $user->id,
            'title' => '他の絵本',
            'authors' => ['著者'],
            'read_status' => 'unread',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $otherBook->id,
                'read_date' => now()->format('Y-m-d'),
                'children' => [
                    ['child_id' => $child->id],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_other_family_child_returns_validation_error(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();

        $otherFamily = Family::create(['name' => 'Other Family']);
        $otherChild = Child::create(['family_id' => $otherFamily->id, 'name' => '他の子', 'birthday' => '2021-01-01']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->format('Y-m-d'),
                'children' => [
                    ['child_id' => $otherChild->id],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_future_date_returns_validation_error(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->addDay()->format('Y-m-d'),
                'children' => [
                    ['child_id' => $child->id],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_non_member_cannot_create_record(): void
    {
        [$family, $user, $child, $book] = $this->createFamilyWithUserAndBook();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/families/{$family->id}/records", [
                'picture_book_id' => $book->id,
                'read_date' => now()->format('Y-m-d'),
                'children' => [
                    ['child_id' => $child->id],
                ],
            ]);

        $response->assertStatus(403);
    }
}
