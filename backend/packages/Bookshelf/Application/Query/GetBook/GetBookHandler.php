<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\GetBook;

use App\Models\PictureBook;

final class GetBookHandler
{
    public function handle(GetBookQuery $query): ?PictureBook
    {
        return PictureBook::find($query->bookId);
    }
}
