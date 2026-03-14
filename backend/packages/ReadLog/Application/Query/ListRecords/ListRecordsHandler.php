<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\ListRecords;

use App\Models\ReadRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 読み聞かせ記録一覧をフィルター・ページネーション付きで取得する。
 */
final class ListRecordsHandler
{
    public function handle(ListRecordsQuery $query): LengthAwarePaginator
    {
        $builder = ReadRecord::with(['children', 'tags', 'pictureBook', 'recordedByUser'])
            ->where('family_id', $query->familyId);

        if ($query->childId !== null) {
            $builder->whereHas('children', function ($q) use ($query) {
                $q->where('children.id', $query->childId);
            });
        }

        if ($query->pictureBookId !== null) {
            $builder->where('picture_book_id', $query->pictureBookId);
        }

        if ($query->dateFrom !== null) {
            $builder->where('read_date', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null) {
            $builder->where('read_date', '<=', $query->dateTo);
        }

        return $builder->orderBy('read_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
