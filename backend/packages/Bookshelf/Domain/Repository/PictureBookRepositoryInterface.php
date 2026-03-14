<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\Repository;

use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;

/**
 * 絵本リポジトリインターフェース。
 *
 * 絵本エンティティの永続化・検索・削除を担う。
 */
interface PictureBookRepositoryInterface
{
    /**
     * IDで絵本を検索する。
     *
     * @return ?PictureBook 見つからない場合はnull
     */
    public function findById(PictureBookId $id): ?PictureBook;

    /**
     * 家族IDとGoogle Books IDの組み合わせで絵本を検索する。
     *
     * @return ?PictureBook 見つからない場合はnull
     */
    public function findByFamilyIdAndGoogleBooksId(FamilyId $familyId, string $googleBooksId): ?PictureBook;

    /**
     * 絵本を保存（新規登録または更新）する。
     *
     * @return PictureBook IDが付与された絵本エンティティ
     */
    public function save(PictureBook $book): PictureBook;

    /**
     * 絵本を削除する。
     */
    public function delete(PictureBookId $id): void;
}
