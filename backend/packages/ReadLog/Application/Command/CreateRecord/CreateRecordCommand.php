<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\CreateRecord;

/**
 * 読み聞かせ記録作成コマンドDTO。
 */
final class CreateRecordCommand
{
    /**
     * @param array<int, string|null> $childReactions [child_id => reaction]
     * @param string[] $tags タグ名の配列
     */
    public function __construct(
        public readonly int $pictureBookId,
        public readonly int $familyId,
        public readonly int $userId,
        public readonly string $readDate,
        public readonly ?string $memo,
        public readonly array $childReactions,
        public readonly array $tags,
    ) {}
}
