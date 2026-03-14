<?php

declare(strict_types=1);

namespace Packages\ReadLog\Infrastructure\Validator;

use App\Models\Child;
use App\Models\PictureBook;
use Packages\ReadLog\Application\Validator\FamilyOwnershipValidatorInterface;
use Packages\Shared\ValueObject\ChildId;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;

/**
 * Eloquentを使用した家族所有権バリデーターの実装。
 */
final readonly class EloquentFamilyOwnershipValidator implements FamilyOwnershipValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function allChildrenBelongToFamily(FamilyId $familyId, array $childIds): bool
    {
        if (empty($childIds)) {
            return true;
        }

        $ids = array_map(fn (ChildId $id) => $id->value(), $childIds);

        $count = Child::where('family_id', $familyId->value())
            ->whereIn('id', $ids)
            ->count();

        return $count === count($childIds);
    }

    /**
     * {@inheritdoc}
     */
    public function pictureBookBelongsToFamily(FamilyId $familyId, PictureBookId $pictureBookId): bool
    {
        return PictureBook::where('id', $pictureBookId->value())
            ->where('family_id', $familyId->value())
            ->exists();
    }
}
