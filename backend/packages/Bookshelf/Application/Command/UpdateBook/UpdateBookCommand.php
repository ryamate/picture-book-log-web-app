<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\UpdateBook;

/**
 * Command DTO for updating a picture book's review information.
 */
final class UpdateBookCommand
{
    /**
     * @param int         $bookId     ID of the picture book to update
     * @param int|null    $rating     Rating value (nullable)
     * @param string      $readStatus Read status identifier
     * @param string|null $review     Review text (nullable)
     */
    public function __construct(
        public readonly int $bookId,
        public readonly ?int $rating,
        public readonly string $readStatus,
        public readonly ?string $review,
    ) {}
}
