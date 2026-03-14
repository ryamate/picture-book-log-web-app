<?php

namespace Tests\Feature\Bookshelf;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchGoogleBooksTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleBooksResponse(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'id' => 'test_google_id',
                        'volumeInfo' => [
                            'title' => 'ぐりとぐら',
                            'authors' => ['中川李枝子'],
                            'industryIdentifiers' => [
                                ['type' => 'ISBN_13', 'identifier' => '9784834000825'],
                            ],
                            'imageLinks' => [
                                'thumbnail' => 'http://example.com/thumb.jpg',
                            ],
                            'publishedDate' => '1967-01-20',
                            'description' => 'A classic picture book',
                            'pageCount' => 28,
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_authenticated_user_can_search_google_books(): void
    {
        $this->fakeGoogleBooksResponse();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books/search?q=ぐりとぐら');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_items',
                'items' => [
                    '*' => [
                        'google_books_id',
                        'title',
                        'authors',
                        'isbn',
                        'thumbnail_url',
                    ],
                ],
            ]);

        $this->assertEquals('https://example.com/thumb.jpg', $response->json('items.0.thumbnail_url'));
    }

    public function test_search_without_query_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books/search');

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_search(): void
    {
        $response = $this->getJson('/api/v1/books/search?q=test');

        $response->assertStatus(401);
    }
}
