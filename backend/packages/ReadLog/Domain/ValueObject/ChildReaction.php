<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use Packages\Shared\ValueObject\ChildId;

/**
 * 子どもとリアクションのペアを表す値オブジェクト。
 *
 * 読み聞かせ記録における、特定の子どものリアクションを保持する。
 */
final readonly class ChildReaction
{
    /**
     * @param  ChildId  $childId  子どもID
     * @param  Reaction|null  $reaction  リアクション（任意）
     */
    public function __construct(
        private ChildId $childId,
        private ?Reaction $reaction,
    ) {}

    public function childId(): ChildId
    {
        return $this->childId;
    }

    public function reaction(): ?Reaction
    {
        return $this->reaction;
    }
}
