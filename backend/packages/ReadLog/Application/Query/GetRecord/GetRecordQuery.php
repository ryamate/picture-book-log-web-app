<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\GetRecord;

/**
 * 読み聞かせ記録詳細取得クエリDTO。
 */
final class GetRecordQuery
{
    public function __construct(
        public readonly int $recordId,
    ) {}
}
