<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\RemoveBook;

use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Shared\ValueObject\PictureBookId;

/**
 * 本棚から絵本を削除する処理を行う。
 */
final readonly class RemoveBookHandler
{
    public function __construct(
        private PictureBookRepositoryInterface $pictureBookRepository,
    ) {}

    /**
     * 指定された絵本を削除する。
     *
     * @param  RemoveBookCommand $command
     * @return void
     */
    public function handle(RemoveBookCommand $command): void
    {
        $this->pictureBookRepository->delete(new PictureBookId($command->bookId));
    }
}
