<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\UpdateFamily;

use Packages\Family\Domain\Entity\Family;
use Packages\Family\Domain\Repository\FamilyRepositoryInterface;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Family\Domain\ValueObject\FamilyName;

final class UpdateFamilyHandler
{
    public function __construct(
        private readonly FamilyRepositoryInterface $familyRepository,
    ) {}

    public function handle(UpdateFamilyCommand $command): Family
    {
        $family = $this->familyRepository->findById(new FamilyId($command->familyId));

        if ($family === null) {
            throw new \RuntimeException("Family not found: {$command->familyId}");
        }

        $family->rename(new FamilyName($command->name));

        return $this->familyRepository->save($family);
    }
}
