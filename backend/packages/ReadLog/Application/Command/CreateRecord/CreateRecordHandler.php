<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\CreateRecord;

use Packages\ReadLog\Domain\Entity\ReadRecord;
use Packages\ReadLog\Domain\Repository\ReadRecordRepositoryInterface;
use Packages\ReadLog\Domain\Repository\TagRepositoryInterface;
use Packages\ReadLog\Domain\ValueObject\ChildReaction;
use Packages\ReadLog\Domain\ValueObject\Reaction;
use Packages\ReadLog\Domain\ValueObject\ReadDate;
use Packages\Shared\ValueObject\ChildId;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;
use Packages\Shared\ValueObject\UserId;

/**
 * 読み聞かせ記録の作成を行う。
 */
final readonly class CreateRecordHandler
{
    public function __construct(
        private ReadRecordRepositoryInterface $readRecordRepository,
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function handle(CreateRecordCommand $command): ReadRecord
    {
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

        $record = ReadRecord::create(
            pictureBookId: new PictureBookId($command->pictureBookId),
            familyId: new FamilyId($command->familyId),
            recordedBy: new UserId($command->userId),
            readDate: new ReadDate($command->readDate),
            memo: $command->memo,
            childReactions: $childReactions,
            tagIds: $tagIds,
        );

        return $this->readRecordRepository->save($record);
    }
}
