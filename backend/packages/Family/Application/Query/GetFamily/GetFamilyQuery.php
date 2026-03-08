<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\GetFamily;

final class GetFamilyQuery
{
    public function __construct(
        public readonly int $familyId,
    ) {}
}
