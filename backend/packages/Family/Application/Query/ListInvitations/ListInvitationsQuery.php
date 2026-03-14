<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\ListInvitations;

final readonly class ListInvitationsQuery
{
    public function __construct(
        public int $familyId,
    ) {}
}
