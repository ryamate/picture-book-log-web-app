<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\GetBook;

/**
 * IDを指定して絵本を1件取得するクエリDTO。
 */
final readonly class GetBookQuery
{
    /**
     * @param int $bookId 取得する絵本のID
     */
    public function __construct(
        public int $bookId,
    ) {}
}
