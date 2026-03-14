<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use InvalidArgumentException;

/**
 * 読んだ日付を表す値オブジェクト。
 *
 * 過去または今日の日付のみ許可し、未来日を拒否する。
 */
final readonly class ReadDate
{
    private string $value;

    /**
     * @param string $value Y-m-d形式の日付文字列
     * @throws InvalidArgumentException 無効な日付または未来日の場合
     */
    public function __construct(string $value)
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            throw new InvalidArgumentException('ReadDate must be a valid date in Y-m-d format.');
        }

        // 時刻を除外して日付のみで比較する
        $dateOnly = $date->setTime(0, 0, 0);
        $today = new \DateTimeImmutable('today');
        if ($dateOnly > $today) {
            throw new InvalidArgumentException('ReadDate must not be a future date.');
        }

        $this->value = $dateOnly->format('Y-m-d');
    }

    /** @return string */
    public function value(): string
    {
        return $this->value;
    }
}
