<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use InvalidArgumentException;

/**
 * タグIDを表す値オブジェクト。
 *
 * 正の整数値を保持する。
 */
final readonly class TagId
{
    /**
     * @param  int  $value  正の整数ID
     *
     * @throws InvalidArgumentException 0以下の値の場合
     */
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('TagId must be a positive integer.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    /**
     * 同一性を比較する。
     *
     * @param  self  $other  比較対象
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
