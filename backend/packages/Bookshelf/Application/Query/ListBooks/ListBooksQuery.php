<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\ListBooks;

final class ListBooksQuery
{
    public function __construct(
        public readonly int $familyId,
        public readonly ?string $status = null,
        public readonly string $sort = 'created_at',
        public readonly string $order = 'desc',
        public readonly int $perPage = 20,
    ) {}
}
