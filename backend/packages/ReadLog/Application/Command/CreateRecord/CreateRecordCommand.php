<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\CreateRecord;

/**
 * 読み聞かせ記録作成コマンドDTO。
 */
final readonly class CreateRecordCommand
{
    /**
     * @param int                      $pictureBookId   絵本ID
     * @param int                      $familyId        家族ID
     * @param int                      $userId          記録者のユーザーID
     * @param string                   $readDate        読み聞かせ日
     * @param string|null              $memo            メモ
     * @param array<int, string|null>  $childReactions  子どもの反応 [child_id => reaction]
     * @param string[]                 $tags            タグ名の配列
     */
    public function __construct(
        public int     $pictureBookId,
        public int     $familyId,
        public int     $userId,
        public string  $readDate,
        public ?string $memo,
        public array   $childReactions,
        public array   $tags,
    ) {}
}
