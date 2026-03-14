<?php

declare(strict_types=1);

namespace Packages\ReadLog\Infrastructure\Repository;

use App\Models\ReadRecord as EloquentReadRecord;
use Packages\ReadLog\Domain\Entity\ReadRecord;
use Packages\ReadLog\Domain\Repository\ReadRecordRepositoryInterface;
use Packages\ReadLog\Domain\ValueObject\ChildReaction;
use Packages\ReadLog\Domain\ValueObject\Reaction;
use Packages\ReadLog\Domain\ValueObject\ReadDate;
use Packages\ReadLog\Domain\ValueObject\ReadRecordId;
use Packages\ReadLog\Domain\ValueObject\TagId;
use Packages\Shared\ValueObject\ChildId;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;
use Packages\Shared\ValueObject\UserId;

/**
 * ReadRecordRepositoryInterfaceのEloquent ORM実装。
 */
final class EloquentReadRecordRepository implements ReadRecordRepositoryInterface
{
    /**
     * IDで読み聞かせ記録を検索する。
     *
     * @param ReadRecordId $id 読み聞かせ記録ID
     * @return ReadRecord|null
     */
    public function findById(ReadRecordId $id): ?ReadRecord
    {
        $model = EloquentReadRecord::with(['children', 'tags'])->find($id->value());

        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * 読み聞かせ記録を保存する（新規の場合は作成、既存の場合は更新）。
     *
     * @param ReadRecord $record 読み聞かせ記録エンティティ
     * @return ReadRecord
     */
    public function save(ReadRecord $record): ReadRecord
    {
        if ($record->id() === null) {
            $model = EloquentReadRecord::create([
                'picture_book_id' => $record->pictureBookId()->value(),
                'family_id' => $record->familyId()->value(),
                'recorded_by' => $record->recordedBy()->value(),
                'read_date' => $record->readDate()->value(),
                'memo' => $record->memo(),
            ]);
        } else {
            $model = EloquentReadRecord::findOrFail($record->id()->value());
            $model->update([
                'read_date' => $record->readDate()->value(),
                'memo' => $record->memo(),
            ]);
        }

        // ピボットテーブル sync: children + reaction
        $childrenSync = [];
        foreach ($record->childReactions() as $cr) {
            $childrenSync[$cr->childId()->value()] = ['reaction' => $cr->reaction()?->value()];
        }
        $model->children()->sync($childrenSync);

        // ピボットテーブル sync: tags
        $tagIds = array_map(fn ($tagId) => $tagId->value(), $record->tagIds());
        $model->tags()->sync($tagIds);

        return $this->toDomainEntity($model->fresh(['children', 'tags']));
    }

    /**
     * IDで読み聞かせ記録を削除する。
     *
     * @param ReadRecordId $id 読み聞かせ記録ID
     * @return void
     */
    public function delete(ReadRecordId $id): void
    {
        EloquentReadRecord::destroy($id->value());
    }

    /**
     * Eloquentモデルをドメインエンティティに変換する。
     *
     * @param EloquentReadRecord $model Eloquentモデル
     * @return ReadRecord
     */
    private function toDomainEntity(EloquentReadRecord $model): ReadRecord
    {
        $childReactions = $model->children->map(function ($child) {
            return new ChildReaction(
                new ChildId($child->id),
                $child->pivot->reaction !== null ? new Reaction($child->pivot->reaction) : null,
            );
        })->all();

        $tagIds = $model->tags->map(function ($tag) {
            return new TagId($tag->id);
        })->all();

        return ReadRecord::reconstruct(
            id: new ReadRecordId($model->id),
            pictureBookId: new PictureBookId($model->picture_book_id),
            familyId: new FamilyId($model->family_id),
            recordedBy: new UserId($model->recorded_by),
            readDate: new ReadDate($model->read_date->format('Y-m-d')),
            memo: $model->memo,
            childReactions: $childReactions,
            tagIds: $tagIds,
        );
    }
}
