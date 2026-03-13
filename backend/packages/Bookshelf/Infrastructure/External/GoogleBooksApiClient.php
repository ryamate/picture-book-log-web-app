<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Infrastructure\External;

use Illuminate\Support\Facades\Http;

/**
 * HTTP client for the Google Books API.
 *
 * Searches volumes and transforms the API response into a normalized format.
 */
final class GoogleBooksApiClient
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * Search Google Books by keyword.
     *
     * @param string $keyword
     * @param int $startIndex
     * @param int $maxResults
     * @return array{total_items: int, items: array<int, array>}
     */
    public function search(string $keyword, int $startIndex = 0, int $maxResults = 20): array
    {
        $params = [
            'q' => $keyword,
            'startIndex' => $startIndex,
            'maxResults' => $maxResults,
            'langRestrict' => 'ja',
            'printType' => 'books',
        ];

        $apiKey = config('services.google_books.api_key');
        if ($apiKey) {
            $params['key'] = $apiKey;
        }

        $response = Http::get(self::BASE_URL, $params);

        $response->throw();

        return $this->transformResponse($response->json());
    }

    /**
     * Transform the raw API response into a normalized structure.
     *
     * @param array $data
     * @return array{total_items: int, items: array<int, array>}
     */
    private function transformResponse(array $data): array
    {
        $totalItems = $data['totalItems'] ?? 0;
        $items = array_map(fn (array $item) => $this->transformItem($item), $data['items'] ?? []);

        return [
            'total_items' => $totalItems,
            'items' => $items,
        ];
    }

    /**
     * Transform a single volume item into a normalized array.
     *
     * @param array $item
     * @return array{google_books_id: string, title: string, authors: array, isbn: ?string, thumbnail_url: ?string, published_date: ?string, description: ?string, page_count: ?int}
     */
    private function transformItem(array $item): array
    {
        $volumeInfo = $item['volumeInfo'] ?? [];
        $isbn13 = $this->extractIsbn($volumeInfo['industryIdentifiers'] ?? [], 'ISBN_13');
        $isbn10 = $this->extractIsbn($volumeInfo['industryIdentifiers'] ?? [], 'ISBN_10');
        $thumbnailUrl = $volumeInfo['imageLinks']['thumbnail'] ?? null;

        if ($thumbnailUrl !== null) {
            $thumbnailUrl = str_replace('http://', 'https://', $thumbnailUrl);
        }

        return [
            'google_books_id' => $item['id'],
            'title' => $volumeInfo['title'] ?? '',
            'authors' => $volumeInfo['authors'] ?? [],
            'isbn' => $isbn13 ?? $isbn10,
            'thumbnail_url' => $thumbnailUrl,
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'description' => $volumeInfo['description'] ?? null,
            'page_count' => $volumeInfo['pageCount'] ?? null,
        ];
    }

    /**
     * Extract an ISBN of the given type from industry identifiers.
     *
     * @param array $identifiers
     * @param string $type ISBN type (e.g. 'ISBN_13', 'ISBN_10')
     * @return string|null
     */
    private function extractIsbn(array $identifiers, string $type): ?string
    {
        foreach ($identifiers as $identifier) {
            if ($identifier['type'] === $type) {
                return $identifier['identifier'];
            }
        }

        return null;
    }
}
