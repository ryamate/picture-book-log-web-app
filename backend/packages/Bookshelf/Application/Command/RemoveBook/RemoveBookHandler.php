<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\RemoveBook;

use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Shared\ValueObject\PictureBookId;

final class RemoveBookHandler
{
    public function __construct(
        private readonly PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

    public function handle(RemoveBookCommand $command): void
    {
        $this->pictureBookRepository->delete(new PictureBookId($command->bookId));
    }
}
