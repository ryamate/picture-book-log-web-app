<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\UpdateBook;

final class UpdateBookCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?int $rating,
        public readonly string $readStatus,
        public readonly ?string $review,
    ) {}
}
