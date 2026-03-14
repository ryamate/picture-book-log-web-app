<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Repository;

use Packages\Family\Domain\Entity\Invitation;
use Packages\Family\Domain\ValueObject\InvitationId;
use Packages\Family\Domain\ValueObject\InvitationToken;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\FamilyId;

interface InvitationRepositoryInterface
{
    public function findById(InvitationId $id): ?Invitation;

    public function findByToken(InvitationToken $token): ?Invitation;

    public function findPendingByFamilyIdAndEmail(FamilyId $familyId, Email $email): ?Invitation;

    /** @return Invitation[] */
    public function findByFamilyId(FamilyId $familyId): array;

    public function save(Invitation $invitation): Invitation;

    public function delete(InvitationId $id): void;
}
