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
    public function findByName(string $name): ?Tag
    {
        $model = EloquentTag::where('name', $name)->first();

        return $model ? Tag::reconstruct(new TagId($model->id), $model->name) : null;
    }

    /**
     * @param string[] $names
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names): array
    {
        return array_map(function (string $name) {
            $model = EloquentTag::firstOrCreate(['name' => trim($name)]);

            return Tag::reconstruct(new TagId($model->id), $model->name);
        }, $names);
    }
}
