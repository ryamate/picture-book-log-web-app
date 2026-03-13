<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\SearchGoogleBooks;

final class SearchGoogleBooksQuery
{
    public function __construct(
        public readonly string $keyword,
        public readonly int $startIndex = 0,
        public readonly int $maxResults = 20,
    ) {}
}
