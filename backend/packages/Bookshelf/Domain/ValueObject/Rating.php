<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

use InvalidArgumentException;

/**
 * 絵本の評価（1〜5）を表す値オブジェクト。
 */
final class Rating
{
    /**
     * @param  int  $value  評価値（1〜5）
     *
     * @throws InvalidArgumentException 範囲外の値の場合
     */
    public function __construct(private readonly int $value)
    {
        if ($value < 1 || $value > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
