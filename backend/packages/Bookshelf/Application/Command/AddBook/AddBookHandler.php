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

final class AddBookHandler
{
    public function __construct(
        private readonly PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

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
