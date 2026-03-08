<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\RemoveChild;

final class RemoveChildCommand
{
    public function __construct(
        public readonly int $childId,
    ) {}
}
