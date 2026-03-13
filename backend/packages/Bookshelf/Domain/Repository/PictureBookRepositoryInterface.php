<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\Repository;

use Packages\Bookshelf\Domain\Entity\PictureBook;
use Packages\Family\Domain\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;

interface PictureBookRepositoryInterface
{
    public function findById(PictureBookId $id): ?PictureBook;

    public function findByFamilyIdAndGoogleBooksId(FamilyId $familyId, string $googleBooksId): ?PictureBook;

    public function save(PictureBook $book): PictureBook;

    public function delete(PictureBookId $id): void;
}
