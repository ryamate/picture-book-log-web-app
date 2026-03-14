<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\SendInvitation;

final readonly class SendInvitationCommand
{
    public function __construct(
        public int    $familyId,
        public int    $invitedByUserId,
        public string $email,
    ) {}
}
