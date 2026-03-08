<?php

declare(strict_types=1);

namespace Packages\Family\Infrastructure\Repository;

use App\Models\Family as EloquentFamily;
use Packages\Family\Domain\Entity\Family;
use Packages\Family\Domain\Repository\FamilyRepositoryInterface;
use Packages\Family\Domain\ValueObject\FamilyId;
use Packages\Family\Domain\ValueObject\FamilyName;

final class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function findById(FamilyId $id): ?Family
    {
        $model = EloquentFamily::find($id->value());

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Family $family): Family
    {
        if ($family->id() === null) {
            $model = EloquentFamily::create(['name' => $family->name()->value()]);
        } else {
            $model = EloquentFamily::findOrFail($family->id()->value());
            $model->update(['name' => $family->name()->value()]);
        }

        return $this->toDomainEntity($model);
    }

    private function toDomainEntity(EloquentFamily $model): Family
    {
        return Family::reconstruct(
            new FamilyId($model->id),
            new FamilyName($model->name),
        );
    }
}
