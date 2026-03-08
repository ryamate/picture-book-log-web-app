<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\UpdateFamily;

final class UpdateFamilyCommand
{
    public function __construct(
        public readonly int $familyId,
        public readonly string $name,
    ) {}
}
