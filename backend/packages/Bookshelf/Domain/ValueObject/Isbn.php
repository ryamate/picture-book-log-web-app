<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

use InvalidArgumentException;

final class Isbn
{
    public function __construct(private readonly string $value)
    {
        if (!preg_match('/^\d{10}(\d{3})?$/', $value)) {
            throw new InvalidArgumentException('ISBN must be a 10 or 13 digit string.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
