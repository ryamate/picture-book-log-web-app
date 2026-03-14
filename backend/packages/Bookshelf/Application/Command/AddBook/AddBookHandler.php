<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\AddBook;

use DomainException;
use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Bookshelf\Domain\ValueObject\Authors;
use Packages\Bookshelf\Domain\ValueObject\BookTitle;
use Packages\Bookshelf\Domain\ValueObject\Isbn;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\UserId;

/**
 * 家族の本棚に新しい絵本を登録する処理を行う。
 */
final readonly class AddBookHandler
{
    public function __construct(
        private PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

    /**
     * 新しい絵本を登録する。Google Books IDによる重複を拒否する。
     *
     *
     * @throws DomainException 同じGoogle Books IDの絵本が家族の本棚に既に存在する場合
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
                throw new DomainException('This book is already registered in the bookshelf.');
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
