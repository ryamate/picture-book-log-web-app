<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\CreateFamily;

final class CreateFamilyCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $userId,
    ) {}
}
