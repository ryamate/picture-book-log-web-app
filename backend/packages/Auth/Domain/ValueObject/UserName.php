<?php

declare(strict_types=1);

namespace Packages\Auth\Domain\ValueObject;

use InvalidArgumentException;

final class UserName
{
    public function __construct(private readonly string $value)
    {
        if ($value === '' || mb_strlen($value) > 255) {
            throw new InvalidArgumentException('UserName must be between 1 and 255 characters.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
