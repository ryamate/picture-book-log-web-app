<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\GetBook;

final class GetBookQuery
{
    public function __construct(
        public readonly int $bookId,
    ) {}
}
