<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\RemoveBook;

/**
 * Command DTO for removing a picture book from the bookshelf.
 */
final class RemoveBookCommand
{
    /**
     * @param int $bookId ID of the picture book to remove
     */
    public function __construct(
        public readonly int $bookId,
    ) {}
}
