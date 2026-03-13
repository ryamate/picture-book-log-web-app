<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\AddBook;

final class AddBookCommand
{
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
