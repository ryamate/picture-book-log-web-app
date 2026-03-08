<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Query\GetCurrentUser;

final class GetCurrentUserQuery
{
    public function __construct(
        public readonly int $userId,
    ) {}
}
