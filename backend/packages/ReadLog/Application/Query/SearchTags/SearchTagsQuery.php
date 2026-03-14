<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\SearchTags;

/**
 * タグ検索クエリDTO。
 */
final readonly class SearchTagsQuery
{
    /**
     * @param string $keyword 検索キーワード
     * @param int    $limit   取得件数の上限
     */
    public function __construct(
        public string $keyword,
        public int    $limit = 10,
    ) {}
}
