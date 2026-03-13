<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

use InvalidArgumentException;

final class BookTitle
{
    public function __construct(private readonly string $value)
    {
        if ($value === '' || mb_strlen($value) > 500) {
            throw new InvalidArgumentException('BookTitle must be between 1 and 500 characters.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
