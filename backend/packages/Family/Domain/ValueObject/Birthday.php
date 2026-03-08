<?php

declare(strict_types=1);

namespace Packages\Family\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

final class Birthday
{
    private readonly DateTimeImmutable $value;

    public function __construct(string $date)
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsed === false) {
            throw new InvalidArgumentException('Birthday must be a valid date in Y-m-d format.');
        }

        if ($parsed > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Birthday must not be a future date.');
        }

        $this->value = $parsed;
    }

    public function value(): string
    {
        return $this->value->format('Y-m-d');
    }
}
