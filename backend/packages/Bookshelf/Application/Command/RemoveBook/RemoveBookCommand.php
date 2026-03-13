<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\RemoveBook;

final class RemoveBookCommand
{
    public function __construct(
        public readonly int $bookId,
    ) {}
}
