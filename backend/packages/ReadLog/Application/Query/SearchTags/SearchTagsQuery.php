<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\SearchTags;

/**
 * タグ検索クエリDTO。
 */
final class SearchTagsQuery
{
    public function __construct(
        public readonly string $keyword,
        public readonly int $limit = 10,
    ) {}
}
