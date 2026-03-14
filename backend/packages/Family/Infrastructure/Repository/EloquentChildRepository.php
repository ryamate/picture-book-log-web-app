<?php

declare(strict_types=1);

namespace Packages\Family\Infrastructure\Repository;

use App\Models\Child as EloquentChild;
use Packages\Family\Domain\Entity\Child;
use Packages\Family\Domain\Repository\ChildRepositoryInterface;
use Packages\Family\Domain\ValueObject\Birthday;
use Packages\Shared\ValueObject\ChildId;
use Packages\Family\Domain\ValueObject\ChildName;
use Packages\Shared\ValueObject\FamilyId;

final class EloquentChildRepository implements ChildRepositoryInterface
{
    public function findById(ChildId $id): ?Child
    {
        $model = EloquentChild::find($id->value());

        return $model ? $this->toDomainEntity($model) : null;
    }

    /** @return Child[] */
    public function findByFamilyId(FamilyId $familyId): array
    {
        return EloquentChild::where('family_id', $familyId->value())
            ->get()
            ->map(fn (EloquentChild $model) => $this->toDomainEntity($model))
            ->all();
    }

    public function save(Child $child): Child
    {
        if ($child->id() === null) {
            $model = EloquentChild::create([
                'family_id' => $child->familyId()->value(),
                'name' => $child->name()->value(),
                'birthday' => $child->birthday()?->value(),
            ]);
        } else {
            $model = EloquentChild::findOrFail($child->id()->value());
            $model->update([
                'name' => $child->name()->value(),
                'birthday' => $child->birthday()?->value(),
            ]);
        }

        return $this->toDomainEntity($model);
    }

    public function delete(ChildId $id): void
    {
        EloquentChild::findOrFail($id->value())->delete();
    }

    private function toDomainEntity(EloquentChild $model): Child
    {
        return Child::reconstruct(
            new ChildId($model->id),
            new FamilyId($model->family_id),
            new ChildName($model->name),
            $model->birthday ? new Birthday($model->birthday->format('Y-m-d')) : null,
        );
    }
}
