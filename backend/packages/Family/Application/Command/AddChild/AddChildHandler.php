<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\AddChild;

use Packages\Family\Domain\Entity\Child;
use Packages\Family\Domain\Repository\ChildRepositoryInterface;
use Packages\Family\Domain\ValueObject\Birthday;
use Packages\Family\Domain\ValueObject\ChildName;
use Packages\Family\Domain\ValueObject\FamilyId;

final class AddChildHandler
{
    public function __construct(
        private readonly ChildRepositoryInterface $childRepository,
    ) {}

    public function handle(AddChildCommand $command): Child
    {
        $child = Child::create(
            new FamilyId($command->familyId),
            new ChildName($command->name),
            $command->birthday ? new Birthday($command->birthday) : null,
        );

        return $this->childRepository->save($child);
    }
}
