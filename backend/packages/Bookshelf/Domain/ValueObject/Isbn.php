<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

use InvalidArgumentException;

/**
 * ISBN（国際標準図書番号）を表す値オブジェクト。
 *
 * ISBN-10またはISBN-13形式の数字列を保持する。
 */
final class Isbn
{
    /**
     * @param  string  $value  ISBN文字列（10桁または13桁の数字）
     *
     * @throws InvalidArgumentException 形式が不正な場合
     */
    public function __construct(private readonly string $value)
    {
        if (! preg_match('/^\d{10}(\d{3})?$/', $value)) {
            throw new InvalidArgumentException('ISBN must be a 10 or 13 digit string.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
