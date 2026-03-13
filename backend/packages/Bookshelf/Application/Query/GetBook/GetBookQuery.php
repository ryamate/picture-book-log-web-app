<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\GetBook;

/**
 * Query DTO for retrieving a single picture book by its ID.
 */
final class GetBookQuery
{
    /**
     * @param int $bookId ID of the picture book to retrieve
     */
    public function __construct(
        public readonly int $bookId,
    ) {}
}
