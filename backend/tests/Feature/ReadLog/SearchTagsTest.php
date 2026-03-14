<?php

namespace Tests\Feature\ReadLog;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_tags_by_keyword(): void
    {
        $user = User::factory()->create();
        Tag::create(['name' => '寝る前']);
        Tag::create(['name' => '寝かしつけ']);
        Tag::create(['name' => 'お気に入り']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tags?q=寝');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_search_tags_no_results(): void
    {
        $user = User::factory()->create();
        Tag::create(['name' => '寝る前']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tags?q=存在しない');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(0, $data);
    }

    public function test_search_tags_without_query_returns_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tags');

        $response->assertStatus(422);
    }
}
