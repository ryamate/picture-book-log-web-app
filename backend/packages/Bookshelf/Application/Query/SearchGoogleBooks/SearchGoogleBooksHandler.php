<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\SearchGoogleBooks;

use Packages\Bookshelf\Infrastructure\External\GoogleBooksApiClient;

/**
 * Handles searching for books via the Google Books API.
 */
final class SearchGoogleBooksHandler
{
    public function __construct(
        private readonly GoogleBooksApiClient $googleBooksApiClient,
    ) {}

    /**
     * @param  SearchGoogleBooksQuery $query
     * @return array Search results from Google Books API.
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
