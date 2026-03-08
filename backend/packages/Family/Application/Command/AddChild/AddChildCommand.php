<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\AddChild;

final class AddChildCommand
{
    public function __construct(
        public readonly int $familyId,
        public readonly string $name,
        public readonly ?string $birthday,
    ) {}
}
