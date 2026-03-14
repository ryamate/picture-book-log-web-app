<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Service;

use DateTimeImmutable;
use Packages\Family\Domain\Entity\Invitation;
use Packages\Family\Domain\Exception\DuplicateInvitationException;
use Packages\Family\Domain\Repository\InvitationRepositoryInterface;
use Packages\Family\Domain\ValueObject\InvitationToken;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\UserId;

final readonly class InvitationDomainService
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {}

    public function createInvitation(
        FamilyId $familyId,
        UserId $invitedBy,
        Email $email,
    ): Invitation {
        $existing = $this->invitationRepository->findPendingByFamilyIdAndEmail($familyId, $email);
        if ($existing !== null) {
            throw new DuplicateInvitationException;
        }

        $token = InvitationToken::generate();
        $expiresAt = new DateTimeImmutable('+7 days');

        return Invitation::create($familyId, $invitedBy, $email, $token, $expiresAt);
    }
}
