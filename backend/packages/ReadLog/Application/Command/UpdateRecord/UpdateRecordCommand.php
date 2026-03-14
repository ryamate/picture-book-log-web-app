<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\UpdateRecord;

/**
 * 読み聞かせ記録更新コマンドDTO。
 */
final class UpdateRecordCommand
{
    /**
     * @param array<int, string|null> $childReactions [child_id => reaction]
     * @param string[] $tags タグ名の配列
     */
    public function __construct(
        public readonly int $recordId,
        public readonly string $readDate,
        public readonly ?string $memo,
        public readonly array $childReactions,
        public readonly array $tags,
    ) {}
}
