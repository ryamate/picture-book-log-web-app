<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Infrastructure\Repository;

use App\Models\PictureBook as EloquentPictureBook;
use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Bookshelf\Domain\ValueObject\Authors;
use Packages\Bookshelf\Domain\ValueObject\BookTitle;
use Packages\Bookshelf\Domain\ValueObject\Isbn;
use Packages\Bookshelf\Domain\ValueObject\Rating;
use Packages\Bookshelf\Domain\ValueObject\ReadStatus;
use Packages\Family\Domain\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;
use Packages\Shared\ValueObject\UserId;

/**
 * PictureBookRepositoryInterfaceのEloquent ORM実装。
 *
 * Eloquentモデルとドメインエンティティ間の変換を行い、永続化操作を提供する。
 */
final class EloquentPictureBookRepository implements PictureBookRepositoryInterface
{
    /**
     * IDで絵本を検索する。
     *
     * @param PictureBookId $id 絵本ID
     * @return PictureBook|null
     */
    public function findById(PictureBookId $id): ?PictureBook
    {
        $model = EloquentPictureBook::find($id->value());

        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * 家族IDとGoogle Books IDで絵本を検索する。
     *
     * @param FamilyId $familyId 家族ID
     * @param string $googleBooksId Google Books ID
     * @return PictureBook|null
     */
    public function findByFamilyIdAndGoogleBooksId(FamilyId $familyId, string $googleBooksId): ?PictureBook
    {
        $model = EloquentPictureBook::where('family_id', $familyId->value())
            ->where('google_books_id', $googleBooksId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * 絵本を保存する（新規の場合は作成、既存の場合は更新）。
     *
     * @param PictureBook $book 絵本エンティティ
     * @return PictureBook
     */
    public function save(PictureBook $book): PictureBook
    {
        if ($book->id() === null) {
            $model = EloquentPictureBook::create([
                'family_id' => $book->familyId()->value(),
                'registered_by' => $book->registeredBy()->value(),
                'google_books_id' => $book->googleBooksId(),
                'isbn' => $book->isbn()?->value(),
                'title' => $book->title()->value(),
                'authors' => $book->authors()->toArray(),
                'thumbnail_url' => $book->thumbnailUrl(),
                'rating' => $book->rating()?->value(),
                'read_status' => $book->readStatus()->value,
                'review' => $book->review(),
            ]);
        } else {
            $model = EloquentPictureBook::findOrFail($book->id()->value());
            $model->update([
                'rating' => $book->rating()?->value(),
                'read_status' => $book->readStatus()->value,
                'review' => $book->review(),
            ]);
        }

        return $this->toDomainEntity($model);
    }

    /**
     * IDで絵本を削除する。
     *
     * @param PictureBookId $id 絵本ID
     * @return void
     */
    public function delete(PictureBookId $id): void
    {
        EloquentPictureBook::destroy($id->value());
    }

    /**
     * Eloquentモデルをドメインエンティティに変換する。
     *
     * @param EloquentPictureBook $model Eloquentモデル
     * @return PictureBook
     */
    private function toDomainEntity(EloquentPictureBook $model): PictureBook
    {
        return PictureBook::reconstruct(
            id: new PictureBookId($model->id),
            familyId: new FamilyId($model->family_id),
            registeredBy: new UserId($model->registered_by),
            googleBooksId: $model->google_books_id,
            isbn: $model->isbn ? new Isbn($model->isbn) : null,
            title: new BookTitle($model->title),
            authors: new Authors($model->authors),
            thumbnailUrl: $model->thumbnail_url,
            rating: $model->rating !== null ? new Rating($model->rating) : null,
            readStatus: ReadStatus::from($model->read_status),
            review: $model->review,
        );
    }
}
