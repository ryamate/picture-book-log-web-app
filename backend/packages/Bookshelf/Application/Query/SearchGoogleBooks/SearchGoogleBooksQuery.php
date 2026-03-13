<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\SearchGoogleBooks;

/**
 * Query DTO for searching books via the Google Books API.
 */
final class SearchGoogleBooksQuery
{
    /**
     * @param string $keyword    Search keyword
     * @param int    $startIndex Pagination offset
     * @param int    $maxResults Maximum number of results to return
     */
    public function __construct(
        public readonly string $keyword,
        public readonly int $startIndex = 0,
        public readonly int $maxResults = 20,
    ) {}
}
