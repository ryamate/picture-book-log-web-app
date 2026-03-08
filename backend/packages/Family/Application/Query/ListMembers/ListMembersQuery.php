<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\ListMembers;

final class ListMembersQuery
{
    public function __construct(
        public readonly int $familyId,
    ) {}
}
