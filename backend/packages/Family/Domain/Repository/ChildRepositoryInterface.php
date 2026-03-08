<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Repository;

use Packages\Family\Domain\Entity\Child;
use Packages\Family\Domain\ValueObject\ChildId;
use Packages\Family\Domain\ValueObject\FamilyId;

interface ChildRepositoryInterface
{
    public function findById(ChildId $id): ?Child;

    /** @return Child[] */
    public function findByFamilyId(FamilyId $familyId): array;

    public function save(Child $child): Child;

    public function delete(ChildId $id): void;
}
