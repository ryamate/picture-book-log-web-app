<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

use InvalidArgumentException;

/**
 * 絵本のタイトルを表す値オブジェクト。
 *
 * 1〜500文字の文字列を保持する。
 */
final class BookTitle
{
    /**
     * @param string $value タイトル文字列
     * @throws InvalidArgumentException 空文字または500文字超の場合
     */
    public function __construct(private readonly string $value)
    {
        if ($value === '' || mb_strlen($value) > 500) {
            throw new InvalidArgumentException('BookTitle must be between 1 and 500 characters.');
        }
    }

    /** @return string */
    public function value(): string
    {
        return $this->value;
    }
}
