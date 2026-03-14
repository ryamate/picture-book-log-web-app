<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\CancelInvitation;

final readonly class CancelInvitationCommand
{
    public function __construct(
        public int $invitationId,
    ) {}
}
