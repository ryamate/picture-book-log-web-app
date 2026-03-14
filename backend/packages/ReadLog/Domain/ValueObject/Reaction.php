<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use InvalidArgumentException;

/**
 * リアクションを表す値オブジェクト。
 *
 * 子どもの反応を0〜255文字の自由テキストで保持する。
 */
final readonly class Reaction
{
    /**
     * @param  string  $value  リアクションテキスト
     *
     * @throws InvalidArgumentException 255文字を超える場合
     */
    public function __construct(private string $value)
    {
        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Reaction must be 255 characters or less.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
