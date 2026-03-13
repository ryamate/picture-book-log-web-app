<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\GetBook;

use App\Models\PictureBook;

/**
 * Handles retrieving a single picture book by its ID.
 */
final class GetBookHandler
{
    /**
     * @param  GetBookQuery    $query
     * @return PictureBook|null Returns null if the book is not found.
     */
    public function handle(GetBookQuery $query): ?PictureBook
    {
        return PictureBook::find($query->bookId);
    }
}
