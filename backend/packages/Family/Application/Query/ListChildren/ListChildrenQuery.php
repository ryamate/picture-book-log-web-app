<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\ListChildren;

final class ListChildrenQuery
{
    public function __construct(
        public readonly int $familyId,
    ) {}
}
