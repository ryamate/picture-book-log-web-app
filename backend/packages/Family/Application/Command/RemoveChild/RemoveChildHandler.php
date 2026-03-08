<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\RemoveChild;

use Packages\Family\Domain\Repository\ChildRepositoryInterface;
use Packages\Family\Domain\ValueObject\ChildId;

final class RemoveChildHandler
{
    public function __construct(
        private readonly ChildRepositoryInterface $childRepository,
    ) {}

    public function handle(RemoveChildCommand $command): void
    {
        $this->childRepository->delete(new ChildId($command->childId));
    }
}
