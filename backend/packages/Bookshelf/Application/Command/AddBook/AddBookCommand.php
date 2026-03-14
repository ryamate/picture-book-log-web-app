<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Application\Command\AddBook;

/**
 * 家族の本棚に新しい絵本を追加するコマンドDTO。
 */
final readonly class AddBookCommand
{
    /**
     * @param  int  $familyId  絵本を追加する家族のID
     * @param  int  $userId  絵本を登録するユーザーのID
     * @param  string|null  $googleBooksId  Google BooksのボリュームID
     * @param  string|null  $isbn  絵本のISBN
     * @param  string  $title  絵本のタイトル
     * @param  array  $authors  著者名のリスト
     * @param  string|null  $thumbnailUrl  絵本のサムネイル画像のURL
     */
    public function __construct(
        public int $familyId,
        public int $userId,
        public ?string $googleBooksId,
        public ?string $isbn,
        public string $title,
        public array $authors,
        public ?string $thumbnailUrl,
    ) {}
}
