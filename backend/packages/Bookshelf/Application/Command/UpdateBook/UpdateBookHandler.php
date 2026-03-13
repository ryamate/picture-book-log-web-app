<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\UpdateBook;

use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Bookshelf\Domain\ValueObject\Rating;
use Packages\Bookshelf\Domain\ValueObject\ReadStatus;
use Packages\Shared\ValueObject\PictureBookId;

/**
 * Handles updating review information (rating, read status, review text) for a picture book.
 */
final class UpdateBookHandler
{
    public function __construct(
        private readonly PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

    /**
     * Update the review details of an existing picture book.
     *
     * @param  UpdateBookCommand $command
     * @return PictureBook
     *
     * @throws \DomainException If the picture book is not found.
     */
    public function handle(UpdateBookCommand $command): PictureBook
    {
        $book = $this->pictureBookRepository->findById(new PictureBookId($command->bookId));

        if ($book === null) {
            throw new \DomainException('Picture book not found.');
        }

        $book->updateReview(
            rating: $command->rating !== null ? new Rating($command->rating) : null,
            readStatus: ReadStatus::from($command->readStatus),
            review: $command->review,
        );

        return $this->pictureBookRepository->save($book);
    }
}
