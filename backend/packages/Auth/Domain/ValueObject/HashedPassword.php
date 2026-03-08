<?php

declare(strict_types=1);

namespace Packages\Auth\Domain\ValueObject;

use InvalidArgumentException;

final class HashedPassword
{
    public function __construct(private readonly string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('HashedPassword cannot be empty.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
