<?php

declare(strict_types=1);

namespace Packages\ReadLog\Infrastructure\Repository;

use App\Models\Tag as EloquentTag;
use Packages\ReadLog\Domain\Entity\Tag;
use Packages\ReadLog\Domain\Repository\TagRepositoryInterface;
use Packages\ReadLog\Domain\ValueObject\TagId;

/**
 * TagRepositoryInterfaceのEloquent ORM実装。
 */
final class EloquentTagRepository implements TagRepositoryInterface
{
    /**
     * 名前でタグを検索する。
     *
     * @param string $name タグ名
     * @return Tag|null
     */
    public function findByName(string $name): ?Tag
    {
        $model = EloquentTag::where('name', $name)->first();

        return $model ? Tag::reconstruct(new TagId($model->id), $model->name) : null;
    }

    /**
     * タグ名の配列から既存タグの取得または新規作成を行う。
     *
     * @param string[] $names タグ名の配列
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names): array
    {
        return array_map(static function (string $name) {
            $model = EloquentTag::firstOrCreate(['name' => trim($name)]);

            return Tag::reconstruct(new TagId($model->id), $model->name);
        }, $names);
    }
}
