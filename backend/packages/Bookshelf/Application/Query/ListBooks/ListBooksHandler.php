<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\ListBooks;

use App\Models\PictureBook;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles listing picture books for a family with optional status filtering, sorting, and pagination.
 */
final class ListBooksHandler
{
    /**
     * @param  ListBooksQuery      $query
     * @return LengthAwarePaginator Paginated list of picture books.
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
