<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\GetRecord;

use App\Models\ReadRecord;

/**
 * 読み聞かせ記録の詳細をリレーション含めて取得する。
 */
final class GetRecordHandler
{
    /**
     * 読み聞かせ記録の詳細をリレーション含めて取得する。
     */
    public function handle(GetRecordQuery $query): ?ReadRecord
    {
        return ReadRecord::with(['children', 'tags', 'pictureBook', 'recordedByUser'])
            ->find($query->recordId);
    }
}
