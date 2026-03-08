<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\CreateFamily;

use App\Models\User;
use Packages\Family\Domain\Entity\Family;
use Packages\Family\Domain\Repository\FamilyRepositoryInterface;
use Packages\Family\Domain\ValueObject\FamilyName;

final class CreateFamilyHandler
{
    public function __construct(
        private readonly FamilyRepositoryInterface $familyRepository,
    ) {}

    public function handle(CreateFamilyCommand $command): Family
    {
        $family = Family::create(new FamilyName($command->name));
        $family = $this->familyRepository->save($family);

        User::findOrFail($command->userId)->update([
            'family_id' => $family->id()->value(),
        ]);

        return $family;
    }
}
