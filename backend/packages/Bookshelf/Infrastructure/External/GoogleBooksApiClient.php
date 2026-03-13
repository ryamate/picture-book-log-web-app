<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Infrastructure\External;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Google Books APIのHTTPクライアント。
 *
 * 書籍の検索を行い、APIレスポンスを正規化された形式に変換する。
 */
final class GoogleBooksApiClient
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * キーワードでGoogle Booksを検索する。
     *
     * @param string $keyword 検索キーワード
     * @param int $startIndex 取得開始位置
     * @param int $maxResults 最大取得件数
     * @return array{total_items: int, items: array<int, array>}
     * @throws ConnectionException
     * @throws RequestException
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
     * APIの生レスポンスを正規化された構造に変換する。
     *
     * @param array $data APIレスポンスデータ
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
     * 単一の書籍アイテムを正規化された配列に変換する。
     *
     * @param array $item 書籍アイテム
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
     * 業界識別子から指定された種類のISBNを抽出する。
     *
     * @param array $identifiers 業界識別子の配列
     * @param string $type ISBNの種類（例: 'ISBN_13', 'ISBN_10'）
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
