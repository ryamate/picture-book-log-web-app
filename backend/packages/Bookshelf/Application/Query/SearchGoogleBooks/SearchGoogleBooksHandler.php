<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\SearchGoogleBooks;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Packages\Bookshelf\Infrastructure\External\GoogleBooksApiClient;

/**
 * Google Books APIを使用して書籍を検索する処理を行う。
 */
final readonly class SearchGoogleBooksHandler
{
    public function __construct(
        private GoogleBooksApiClient $googleBooksApiClient,
    ) {}

    /**
     * @return array Google Books APIからの検索結果
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(SearchGoogleBooksQuery $query): array
    {
        return $this->googleBooksApiClient->search(
            $query->keyword,
            $query->startIndex,
            $query->maxResults,
        );
    }
}
