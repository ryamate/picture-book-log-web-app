<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\Entity;

use Packages\Bookshelf\Domain\ValueObject\Authors;
use Packages\Bookshelf\Domain\ValueObject\BookTitle;
use Packages\Bookshelf\Domain\ValueObject\Isbn;
use Packages\Bookshelf\Domain\ValueObject\Rating;
use Packages\Bookshelf\Domain\ValueObject\ReadStatus;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\PictureBookId;
use Packages\Shared\ValueObject\UserId;

/**
 * 絵本エンティティ。
 *
 * 家族の本棚に登録された絵本を表し、読書状況やレビューを管理する。
 */
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

    /**
     * 新しい絵本を本棚に登録する。
     *
     * 初期状態は未読・レビューなしで作成される。
     */
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

    /**
     * 永続化層から絵本エンティティを再構築する。
     */
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

    /**
     * 読書記録（評価・読書状況・感想）を更新する。
     */
    public function updateReview(?Rating $rating, ReadStatus $readStatus, ?string $review): void
    {
        $this->rating = $rating;
        $this->readStatus = $readStatus;
        $this->review = $review;
    }

    /** @return ?PictureBookId */
    public function id(): ?PictureBookId
    {
        return $this->id;
    }

    /** @return FamilyId */
    public function familyId(): FamilyId
    {
        return $this->familyId;
    }

    /** @return UserId */
    public function registeredBy(): UserId
    {
        return $this->registeredBy;
    }

    /** @return ?string */
    public function googleBooksId(): ?string
    {
        return $this->googleBooksId;
    }

    /** @return ?Isbn */
    public function isbn(): ?Isbn
    {
        return $this->isbn;
    }

    /** @return BookTitle */
    public function title(): BookTitle
    {
        return $this->title;
    }

    /** @return Authors */
    public function authors(): Authors
    {
        return $this->authors;
    }

    /** @return ?string */
    public function thumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    /** @return ?Rating */
    public function rating(): ?Rating
    {
        return $this->rating;
    }

    /** @return ReadStatus */
    public function readStatus(): ReadStatus
    {
        return $this->readStatus;
    }

    /** @return ?string */
    public function review(): ?string
    {
        return $this->review;
    }
}
