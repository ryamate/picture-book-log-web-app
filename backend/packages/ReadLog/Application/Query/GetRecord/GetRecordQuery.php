<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\GetRecord;

/**
 * 読み聞かせ記録詳細取得クエリDTO。
 */
final readonly class GetRecordQuery
{
    /**
     * @param  int  $recordId  読み聞かせ記録ID
     */
    public function __construct(
        public int $recordId,
    ) {}
}
