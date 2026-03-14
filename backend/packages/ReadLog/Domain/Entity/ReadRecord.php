<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\Entity;

use Packages\ReadLog\Domain\ValueObject\ChildReaction;
use Packages\ReadLog\Domain\ValueObject\ReadDate;
use Packages\ReadLog\Domain\ValueObject\ReadRecordId;
use Packages\ReadLog\Domain\ValueObject\TagId;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;
use Packages\Shared\ValueObject\UserId;

/**
 * 読み聞かせ記録エンティティ（集約ルート）。
 *
 * 子どもごとのリアクションとタグの紐付けを管理する。
 */
final class ReadRecord
{
    /**
     * @param  ChildReaction[]  $childReactions
     * @param  TagId[]  $tagIds
     */
    private function __construct(
        private readonly ?ReadRecordId $id,
        private readonly PictureBookId $pictureBookId,
        private readonly FamilyId $familyId,
        private readonly UserId $recordedBy,
        private ReadDate $readDate,
        private ?string $memo,
        private array $childReactions,
        private array $tagIds,
    ) {}

    /**
     * 新しい読み聞かせ記録を作成する。
     *
     * @param  ChildReaction[]  $childReactions
     * @param  TagId[]  $tagIds
     */
    public static function create(
        PictureBookId $pictureBookId,
        FamilyId $familyId,
        UserId $recordedBy,
        ReadDate $readDate,
        ?string $memo,
        array $childReactions,
        array $tagIds,
    ): self {
        return new self(
            null, $pictureBookId, $familyId, $recordedBy,
            $readDate, $memo, $childReactions, $tagIds,
        );
    }

    /**
     * 永続化層から読み聞かせ記録を再構築する。
     *
     * @param  ChildReaction[]  $childReactions
     * @param  TagId[]  $tagIds
     */
    public static function reconstruct(
        ReadRecordId $id,
        PictureBookId $pictureBookId,
        FamilyId $familyId,
        UserId $recordedBy,
        ReadDate $readDate,
        ?string $memo,
        array $childReactions,
        array $tagIds,
    ): self {
        return new self(
            $id, $pictureBookId, $familyId, $recordedBy,
            $readDate, $memo, $childReactions, $tagIds,
        );
    }

    /**
     * 記録を更新する。
     *
     * @param  ChildReaction[]  $childReactions
     * @param  TagId[]  $tagIds
     */
    public function update(
        ReadDate $readDate,
        ?string $memo,
        array $childReactions,
        array $tagIds,
    ): void {
        $this->readDate = $readDate;
        $this->memo = $memo;
        $this->childReactions = $childReactions;
        $this->tagIds = $tagIds;
    }

    public function id(): ?ReadRecordId
    {
        return $this->id;
    }

    public function pictureBookId(): PictureBookId
    {
        return $this->pictureBookId;
    }

    public function familyId(): FamilyId
    {
        return $this->familyId;
    }

    public function recordedBy(): UserId
    {
        return $this->recordedBy;
    }

    public function readDate(): ReadDate
    {
        return $this->readDate;
    }

    public function memo(): ?string
    {
        return $this->memo;
    }

    /** @return ChildReaction[] */
    public function childReactions(): array
    {
        return $this->childReactions;
    }

    /** @return TagId[] */
    public function tagIds(): array
    {
        return $this->tagIds;
    }
}
