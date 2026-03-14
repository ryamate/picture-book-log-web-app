<?php

declare(strict_types=1);

namespace Packages\Family\Application\Command\SendInvitation;

use App\Models\Family as EloquentFamily;
use Illuminate\Support\Facades\Mail;
use Packages\Family\Domain\Entity\Invitation;
use Packages\Family\Domain\Repository\InvitationRepositoryInterface;
use Packages\Family\Domain\Service\InvitationDomainService;
use Packages\Family\Infrastructure\Mail\InvitationMail;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\UserId;

final readonly class SendInvitationHandler
{
    public function __construct(
        private InvitationDomainService $domainService,
        private InvitationRepositoryInterface $invitationRepository,
    ) {}

    public function handle(SendInvitationCommand $command): Invitation
    {
        $invitation = $this->domainService->createInvitation(
            new FamilyId($command->familyId),
            new UserId($command->invitedByUserId),
            new Email($command->email),
        );

        $invitation = $this->invitationRepository->save($invitation);

        $family = EloquentFamily::findOrFail($command->familyId);
        $inviter = \App\Models\User::findOrFail($command->invitedByUserId);
        $acceptUrl = config('app.frontend_url').'/invitations/'.$invitation->token()->value().'/accept';

        Mail::to($command->email)->send(
            new InvitationMail($family->name, $inviter->name, $acceptUrl),
        );

        return $invitation;
    }
}
