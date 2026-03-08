<?php

declare(strict_types=1);

namespace Packages\Shared\ValueObject;

use InvalidArgumentException;

final class Email
{
    public function __construct(private readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
