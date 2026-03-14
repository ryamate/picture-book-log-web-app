<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\ValueObject;

use InvalidArgumentException;

final class Reaction
{
    public function __construct(private readonly string $value)
    {
        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Reaction must be 255 characters or less.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
