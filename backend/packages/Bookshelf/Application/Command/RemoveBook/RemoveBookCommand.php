<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\RemoveBook;

/**
 * 本棚から絵本を削除するコマンドDTO。
 */
final readonly class RemoveBookCommand
{
    /**
     * @param int $bookId 削除する絵本のID
     */
    public function __construct(
        public int $bookId,
    ) {}
}
