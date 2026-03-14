<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Validator;

use Packages\Shared\ValueObject\ChildId;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;

/**
 * 家族に対するリソースの所有権を検証するインターフェース。
 *
 * 他の境界づけられたコンテキスト（Family, Bookshelf）が管理するリソースに対して、
 * 家族への所属を確認するためのポートとして機能する。
 */
interface FamilyOwnershipValidatorInterface
{
    /**
     * 指定された子どもが全員、指定の家族に属しているかを検証する。
     *
     * @param FamilyId  $familyId 家族ID
     * @param ChildId[] $childIds 子どもIDの配列
     * @return bool
     */
    public function allChildrenBelongToFamily(FamilyId $familyId, array $childIds): bool;

    /**
     * 指定された絵本が指定の家族に属しているかを検証する。
     *
     * @param FamilyId      $familyId      家族ID
     * @param PictureBookId $pictureBookId 絵本ID
     * @return bool
     */
    public function pictureBookBelongsToFamily(FamilyId $familyId, PictureBookId $pictureBookId): bool;
}
