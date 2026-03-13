<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\Entity;

use Packages\Bookshelf\Domain\ValueObject\Authors;
use Packages\Bookshelf\Domain\ValueObject\BookTitle;
use Packages\Bookshelf\Domain\ValueObject\Isbn;
use Packages\Bookshelf\Domain\ValueObject\Rating;
use Packages\Bookshelf\Domain\ValueObject\ReadStatus;
use Packages\Family\Domain\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;
use Packages\Shared\ValueObject\UserId;

final class PictureBook
{
    private function __construct(
        private readonly ?PictureBookId $id,
        private readonly FamilyId $familyId,
        private readonly UserId $registeredBy,
        private readonly ?string $googleBooksId,
        private readonly ?Isbn $isbn,
        private readonly BookTitle $title,
        private readonly Authors $authors,
        private readonly ?string $thumbnailUrl,
        private ?Rating $rating,
        private ReadStatus $readStatus,
        private ?string $review,
    ) {}

    public static function register(
        FamilyId $familyId,
        UserId $registeredBy,
        ?string $googleBooksId,
        ?Isbn $isbn,
        BookTitle $title,
        Authors $authors,
        ?string $thumbnailUrl,
    ): self {
        return new self(
            id: null,
            familyId: $familyId,
            registeredBy: $registeredBy,
            googleBooksId: $googleBooksId,
            isbn: $isbn,
            title: $title,
            authors: $authors,
            thumbnailUrl: $thumbnailUrl,
            rating: null,
            readStatus: ReadStatus::Unread,
            review: null,
        );
    }

    public static function reconstruct(
        PictureBookId $id,
        FamilyId $familyId,
        UserId $registeredBy,
        ?string $googleBooksId,
        ?Isbn $isbn,
        BookTitle $title,
        Authors $authors,
        ?string $thumbnailUrl,
        ?Rating $rating,
        ReadStatus $readStatus,
        ?string $review,
    ): self {
        return new self(
            id: $id,
            familyId: $familyId,
            registeredBy: $registeredBy,
            googleBooksId: $googleBooksId,
            isbn: $isbn,
            title: $title,
            authors: $authors,
            thumbnailUrl: $thumbnailUrl,
            rating: $rating,
            readStatus: $readStatus,
            review: $review,
        );
    }

    public function updateReview(?Rating $rating, ReadStatus $readStatus, ?string $review): void
    {
        $this->rating = $rating;
        $this->readStatus = $readStatus;
        $this->review = $review;
    }

    public function id(): ?PictureBookId
    {
        return $this->id;
    }

    public function familyId(): FamilyId
    {
        return $this->familyId;
    }

    public function registeredBy(): UserId
    {
        return $this->registeredBy;
    }

    public function googleBooksId(): ?string
    {
        return $this->googleBooksId;
    }

    public function isbn(): ?Isbn
    {
        return $this->isbn;
    }

    public function title(): BookTitle
    {
        return $this->title;
    }

    public function authors(): Authors
    {
        return $this->authors;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function rating(): ?Rating
    {
        return $this->rating;
    }

    public function readStatus(): ReadStatus
    {
        return $this->readStatus;
    }

    public function review(): ?string
    {
        return $this->review;
    }
}
