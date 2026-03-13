<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\ListBooks;

use App\Models\PictureBook;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 家族の絵本一覧を、ステータスによる絞り込み・ソート・ページネーション付きで取得する処理を行う。
 */
final class ListBooksHandler
{
    /**
     * @param  ListBooksQuery      $query
     * @return LengthAwarePaginator ページネーション付きの絵本一覧
     */
    public function handle(ListBooksQuery $query): LengthAwarePaginator
    {
        $builder = PictureBook::where('family_id', $query->familyId);

        if ($query->status !== null) {
            $builder->where('read_status', $query->status);
        }

        $sortColumn = in_array($query->sort, ['created_at', 'title', 'rating'], true)
            ? $query->sort
            : 'created_at';

        $order = in_array($query->order, ['asc', 'desc'], true)
            ? $query->order
            : 'desc';

        return $builder->orderBy($sortColumn, $order)->paginate($query->perPage);
    }
}
