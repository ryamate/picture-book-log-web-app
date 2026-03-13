<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\ListBooks;

/**
 * Query DTO for listing picture books in a family's bookshelf with filtering, sorting, and pagination.
 */
final class ListBooksQuery
{
    /**
     * @param int         $familyId Family whose books to list
     * @param string|null $status   Filter by read status (nullable)
     * @param string      $sort     Sort column (created_at, title, or rating)
     * @param string      $order    Sort direction (asc or desc)
     * @param int         $perPage  Number of items per page
     */
    public function __construct(
        public readonly int $familyId,
        public readonly ?string $status = null,
        public readonly string $sort = 'created_at',
        public readonly string $order = 'desc',
        public readonly int $perPage = 20,
    ) {}
}
