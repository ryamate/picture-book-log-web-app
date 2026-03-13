<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\AddBook;

/**
 * Command DTO for adding a new picture book to a family's bookshelf.
 */
final class AddBookCommand
{
    /**
     * @param int         $familyId       Family to add the book to
     * @param int         $userId         User who is registering the book
     * @param string|null $googleBooksId  Google Books volume ID
     * @param string|null $isbn           ISBN of the book
     * @param string      $title          Book title
     * @param array       $authors        List of author names
     * @param string|null $thumbnailUrl   URL of the book's thumbnail image
     */
    public function __construct(
        public readonly int $familyId,
        public readonly int $userId,
        public readonly ?string $googleBooksId,
        public readonly ?string $isbn,
        public readonly string $title,
        public readonly array $authors,
        public readonly ?string $thumbnailUrl,
    ) {}
}
