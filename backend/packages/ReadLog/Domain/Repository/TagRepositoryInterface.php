<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\Repository;

use Packages\ReadLog\Domain\Entity\Tag;

/**
 * タグのリポジトリインターフェース。
 */
interface TagRepositoryInterface
{
    /**
     * 名前でタグを検索する。
     *
     * @param  string  $name タグ名
     * @return Tag|null
     */
    public function findByName(string $name): ?Tag;

    /**
     * タグ名の配列から、既存タグを取得または新規作成して返す。
     *
     * @param  string[]  $names タグ名の配列
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names): array;
}
