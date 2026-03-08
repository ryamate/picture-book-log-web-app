<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\UpdateChild;

use Packages\Family\Domain\Entity\Child;
use Packages\Family\Domain\Repository\ChildRepositoryInterface;
use Packages\Family\Domain\ValueObject\Birthday;
use Packages\Family\Domain\ValueObject\ChildId;
use Packages\Family\Domain\ValueObject\ChildName;

final class UpdateChildHandler
{
    public function __construct(
        private readonly ChildRepositoryInterface $childRepository,
    ) {}

    public function handle(UpdateChildCommand $command): Child
    {
        $child = $this->childRepository->findById(new ChildId($command->childId));

        $child->update(
            new ChildName($command->name),
            $command->birthday ? new Birthday($command->birthday) : null,
        );

        return $this->childRepository->save($child);
    }
}
