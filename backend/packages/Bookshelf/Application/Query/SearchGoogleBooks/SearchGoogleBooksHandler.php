<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\SearchGoogleBooks;

use Packages\Bookshelf\Infrastructure\External\GoogleBooksApiClient;

final class SearchGoogleBooksHandler
{
    public function __construct(
        private readonly GoogleBooksApiClient $googleBooksApiClient,
    ) {}

    public function handle(SearchGoogleBooksQuery $query): array
    {
        return $this->googleBooksApiClient->search(
            $query->keyword,
            $query->startIndex,
            $query->maxResults,
        );
    }
}
