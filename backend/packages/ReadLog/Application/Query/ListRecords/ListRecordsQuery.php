<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\ListRecords;

/**
 * 読み聞かせ記録一覧取得クエリDTO。
 */
final readonly class ListRecordsQuery
{
    /**
     * @param int         $familyId       家族ID
     * @param int|null    $childId        子どもIDによる絞り込み
     * @param int|null    $pictureBookId  絵本IDによる絞り込み
     * @param string|null $dateFrom       読み聞かせ日の開始日
     * @param string|null $dateTo         読み聞かせ日の終了日
     * @param int         $perPage        1ページあたりの件数
     * @param int         $page           ページ番号
     */
    public function __construct(
        public int     $familyId,
        public ?int    $childId = null,
        public ?int    $pictureBookId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int     $perPage = 20,
        public int     $page = 1,
    ) {}
}
