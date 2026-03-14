<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\AcceptInvitation;

final readonly class AcceptInvitationCommand
{
    public function __construct(
        public string $token,
        public int $userId,
    ) {}
}
