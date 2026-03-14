<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use InvalidArgumentException;

final class ReadDate
{
    private readonly string $value;

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

    public function value(): string
    {
        return $this->value;
    }
}
