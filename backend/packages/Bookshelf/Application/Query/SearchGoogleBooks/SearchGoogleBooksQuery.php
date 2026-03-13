<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\SearchGoogleBooks;

/**
 * Google Books APIを使用して書籍を検索するクエリDTO。
 */
final readonly class SearchGoogleBooksQuery
{
    /**
     * @param string $keyword    検索キーワード
     * @param int    $startIndex ページネーションのオフセット
     * @param int    $maxResults 返却する最大件数
     */
    public function __construct(
        public string $keyword,
        public int    $startIndex = 0,
        public int    $maxResults = 20,
    ) {}
}
