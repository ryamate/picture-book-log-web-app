<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\UpdateChild;

final class UpdateChildCommand
{
    public function __construct(
        public readonly int $childId,
        public readonly string $name,
        public readonly ?string $birthday,
    ) {}
}
