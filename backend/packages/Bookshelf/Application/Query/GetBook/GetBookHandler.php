<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\GetBook;

use App\Models\PictureBook;

/**
 * IDを指定して絵本を1件取得する処理を行う。
 */
final class GetBookHandler
{
    /**
     * @param  GetBookQuery    $query
     * @return PictureBook|null 絵本が見つからない場合はnullを返す
     */
    public function handle(GetBookQuery $query): ?PictureBook
    {
        return PictureBook::find($query->bookId);
    }
}
