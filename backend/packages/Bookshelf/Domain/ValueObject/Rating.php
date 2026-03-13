<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

use InvalidArgumentException;

final class Rating
{
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
