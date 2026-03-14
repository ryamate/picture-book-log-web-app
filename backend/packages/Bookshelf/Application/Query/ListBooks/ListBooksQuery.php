<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Query\ListBooks;

/**
 * 家族の本棚の絵本一覧を、絞り込み・ソート・ページネーション付きで取得するクエリDTO。
 */
final readonly class ListBooksQuery
{
    /**
     * @param  int  $familyId  絵本一覧を取得する家族のID
     * @param  string|null  $status  読書ステータスによる絞り込み（null許容）
     * @param  string  $sort  ソートカラム（created_at, title, rating）
     * @param  string  $order  ソート方向（asc または desc）
     * @param  int  $perPage  1ページあたりの表示件数
     */
    public function __construct(
        public int $familyId,
        public ?string $status = null,
        public string $sort = 'created_at',
        public string $order = 'desc',
        public int $perPage = 20,
    ) {}
}
