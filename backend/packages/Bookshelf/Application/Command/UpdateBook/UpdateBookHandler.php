<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\UpdateBook;

use DomainException;
use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Bookshelf\Domain\ValueObject\Rating;
use Packages\Bookshelf\Domain\ValueObject\ReadStatus;
use Packages\Shared\ValueObject\PictureBookId;

/**
 * 絵本のレビュー情報（評価、読書ステータス、レビューテキスト）を更新する処理を行う。
 */
final readonly class UpdateBookHandler
{
    public function __construct(
        private PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

    /**
     * 既存の絵本のレビュー詳細を更新する。
     *
     * @param  UpdateBookCommand $command
     * @return PictureBook
     *
     * @throws DomainException 絵本が見つからない場合
     */
    public function handle(UpdateBookCommand $command): PictureBook
    {
        $book = $this->pictureBookRepository->findById(new PictureBookId($command->bookId));

        if ($book === null) {
            throw new DomainException('Picture book not found.');
        }

        $book->updateReview(
            rating: $command->rating !== null ? new Rating($command->rating) : null,
            readStatus: ReadStatus::from($command->readStatus),
            review: $command->review,
        );

        return $this->pictureBookRepository->save($book);
    }
}
