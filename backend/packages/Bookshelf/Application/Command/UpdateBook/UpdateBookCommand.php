<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\UpdateBook;

/**
 * 絵本のレビュー情報を更新するコマンドDTO。
 */
final readonly class UpdateBookCommand
{
    /**
     * @param  int  $bookId  更新する絵本のID
     * @param  int|null  $rating  評価値（null許容）
     * @param  string  $readStatus  読書ステータスの識別子
     * @param  string|null  $review  レビューテキスト（null許容）
     */
    public function __construct(
        public int $bookId,
        public ?int $rating,
        public string $readStatus,
        public ?string $review,
    ) {}
}
