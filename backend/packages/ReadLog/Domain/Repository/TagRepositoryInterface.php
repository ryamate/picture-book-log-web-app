<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\Repository;

use Packages\ReadLog\Domain\Entity\Tag;

interface TagRepositoryInterface
{
    public function findByName(string $name): ?Tag;

    /**
     * タグ名の配列から、既存タグを取得 or 新規作成して返す。
     *
     * @param string[] $names
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names): array;
}
