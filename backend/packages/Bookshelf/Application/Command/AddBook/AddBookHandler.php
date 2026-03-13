<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\AddBook;

use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Bookshelf\Domain\ValueObject\Authors;
use Packages\Bookshelf\Domain\ValueObject\BookTitle;
use Packages\Bookshelf\Domain\ValueObject\Isbn;
use Packages\Family\Domain\ValueObject\FamilyId;
use Packages\Shared\ValueObject\UserId;

/**
 * Handles registering a new picture book to a family's bookshelf.
 */
final class AddBookHandler
{
    public function __construct(
        private readonly PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

    /**
     * Register a new picture book. Rejects duplicates by Google Books ID.
     *
     * @param  AddBookCommand   $command
     * @return PictureBook
     *
     * @throws \DomainException If a book with the same Google Books ID already exists in the family's bookshelf.
     */
    public function handle(AddBookCommand $command): PictureBook
    {
        $familyId = new FamilyId($command->familyId);

        if ($command->googleBooksId !== null) {
            $existing = $this->pictureBookRepository->findByFamilyIdAndGoogleBooksId(
                $familyId,
                $command->googleBooksId,
            );
            if ($existing !== null) {
                throw new \DomainException('This book is already registered in the bookshelf.');
            }
        }

        $book = PictureBook::register(
            familyId: $familyId,
            registeredBy: new UserId($command->userId),
            googleBooksId: $command->googleBooksId,
            isbn: $command->isbn ? new Isbn($command->isbn) : null,
            title: new BookTitle($command->title),
            authors: new Authors($command->authors),
            thumbnailUrl: $command->thumbnailUrl,
        );

        return $this->pictureBookRepository->save($book);
    }
}
