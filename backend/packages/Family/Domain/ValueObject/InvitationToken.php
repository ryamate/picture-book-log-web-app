<?php

declare(strict_types=1);

namespace Packages\Family\Domain\ValueObject;

use InvalidArgumentException;

final readonly class InvitationToken
{
    private function __construct(
        private string $value,
    ) {
        if (strlen($value) !== 64) {
            throw new InvalidArgumentException('Token must be 64 characters.');
        }
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
