<?php

declare(strict_types=1);

namespace Packages\Shared\ValueObject;

use InvalidArgumentException;

final class PictureBookId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('PictureBookId must be a positive integer.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
