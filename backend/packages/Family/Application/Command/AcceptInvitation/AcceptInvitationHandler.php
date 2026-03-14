<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\AcceptInvitation;

use App\Models\User;
use Packages\Family\Domain\Entity\Invitation;
use Packages\Family\Domain\Exception\UserAlreadyInFamilyException;
use Packages\Family\Domain\Repository\InvitationRepositoryInterface;
use Packages\Family\Domain\ValueObject\InvitationToken;
use RuntimeException;

final readonly class AcceptInvitationHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {}

    public function handle(AcceptInvitationCommand $command): Invitation
    {
        $invitation = $this->invitationRepository->findByToken(
            InvitationToken::fromString($command->token),
        );

        if ($invitation === null) {
            throw new RuntimeException('Invitation not found.');
        }

        $invitation->accept();

        $user = User::findOrFail($command->userId);
        if ($user->family_id !== null) {
            throw new UserAlreadyInFamilyException;
        }

        $user->update([
            'family_id' => $invitation->familyId()->value(),
        ]);

        return $this->invitationRepository->save($invitation);
    }
}
