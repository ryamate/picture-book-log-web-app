<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\UpdateRecord;

/**
 * 読み聞かせ記録更新コマンドDTO。
 */
final readonly class UpdateRecordCommand
{
    /**
     * @param int                      $recordId        読み聞かせ記録ID
     * @param string                   $readDate        読み聞かせ日
     * @param string|null              $memo            メモ
     * @param array<int, string|null>  $childReactions  子どもの反応 [child_id => reaction]
     * @param string[]                 $tags            タグ名の配列
     */
    public function __construct(
        public int     $recordId,
        public string  $readDate,
        public ?string $memo,
        public array   $childReactions,
        public array   $tags,
    ) {}
}
