<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Repository;

use Packages\Family\Domain\Entity\Family;
use Packages\Shared\ValueObject\FamilyId;

interface FamilyRepositoryInterface
{
    public function findById(FamilyId $id): ?Family;

    public function save(Family $family): Family;
}
