<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use Packages\Shared\ValueObject\ChildId;

final class ChildReaction
{
    public function __construct(
        private readonly ChildId $childId,
        private readonly ?Reaction $reaction,
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
