<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\UpdateRecord;

use Packages\ReadLog\Domain\Entity\ReadRecord;
use Packages\ReadLog\Domain\Repository\ReadRecordRepositoryInterface;
use Packages\ReadLog\Domain\Repository\TagRepositoryInterface;
use Packages\ReadLog\Domain\ValueObject\ChildReaction;
use Packages\ReadLog\Domain\ValueObject\Reaction;
use Packages\ReadLog\Domain\ValueObject\ReadDate;
use Packages\ReadLog\Domain\ValueObject\ReadRecordId;
use Packages\Shared\ValueObject\ChildId;

/**
 * 読み聞かせ記録の更新を行う。
 */
final readonly class UpdateRecordHandler
{
    public function __construct(
        private ReadRecordRepositoryInterface $readRecordRepository,
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function handle(UpdateRecordCommand $command): ReadRecord
    {
        $record = $this->readRecordRepository->findById(new ReadRecordId($command->recordId));

        // タグの取得 or 新規作成
        $tags = ! empty($command->tags)
            ? $this->tagRepository->findOrCreateByNames($command->tags)
            : [];
        $tagIds = array_map(fn ($tag) => $tag->id(), $tags);

        // childReactions の変換
        $childReactions = [];
        foreach ($command->childReactions as $childId => $reaction) {
            $childReactions[] = new ChildReaction(
                new ChildId($childId),
                $reaction !== null ? new Reaction($reaction) : null,
            );
        }

        $record->update(
            readDate: new ReadDate($command->readDate),
            memo: $command->memo,
            childReactions: $childReactions,
            tagIds: $tagIds,
        );

        return $this->readRecordRepository->save($record);
    }
}
