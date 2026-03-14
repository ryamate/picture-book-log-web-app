<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\CancelInvitation;

use Packages\Family\Domain\Exception\InvitationAlreadyAcceptedException;
use Packages\Family\Domain\Repository\InvitationRepositoryInterface;
use Packages\Family\Domain\ValueObject\InvitationId;
use RuntimeException;

final readonly class CancelInvitationHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {}

    public function handle(CancelInvitationCommand $command): void
    {
        $invitation = $this->invitationRepository->findById(
            new InvitationId($command->invitationId),
        );

        if ($invitation === null) {
            throw new RuntimeException('Invitation not found.');
        }

        if ($invitation->isAccepted()) {
            throw new InvitationAlreadyAcceptedException;
        }

        $this->invitationRepository->delete(new InvitationId($command->invitationId));
    }
}
