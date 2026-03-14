<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\ListRecords;

/**
 * 読み聞かせ記録一覧取得クエリDTO。
 */
final class ListRecordsQuery
{
    public function __construct(
        public readonly int $familyId,
        public readonly ?int $childId = null,
        public readonly ?int $pictureBookId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
