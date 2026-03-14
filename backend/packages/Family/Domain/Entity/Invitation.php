<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Entity;

use DateTimeImmutable;
use Packages\Family\Domain\Exception\InvitationAlreadyAcceptedException;
use Packages\Family\Domain\Exception\InvitationExpiredException;
use Packages\Family\Domain\ValueObject\InvitationId;
use Packages\Family\Domain\ValueObject\InvitationToken;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\UserId;

final class Invitation
{
    private function __construct(
        private readonly ?InvitationId $id,
        private readonly FamilyId $familyId,
        private readonly UserId $invitedBy,
        private readonly Email $email,
        private readonly InvitationToken $token,
        private ?DateTimeImmutable $acceptedAt,
        private readonly DateTimeImmutable $expiresAt,
    ) {}

    public static function create(
        FamilyId $familyId,
        UserId $invitedBy,
        Email $email,
        InvitationToken $token,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            null, $familyId, $invitedBy, $email, $token, null, $expiresAt,
        );
    }

    public static function reconstruct(
        InvitationId $id,
        FamilyId $familyId,
        UserId $invitedBy,
        Email $email,
        InvitationToken $token,
        ?DateTimeImmutable $acceptedAt,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            $id, $familyId, $invitedBy, $email, $token, $acceptedAt, $expiresAt,
        );
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable > $this->expiresAt;
    }

    public function isAccepted(): bool
    {
        return $this->acceptedAt !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    public function accept(): void
    {
        if ($this->isExpired()) {
            throw new InvitationExpiredException;
        }
        if ($this->isAccepted()) {
            throw new InvitationAlreadyAcceptedException;
        }
        $this->acceptedAt = new DateTimeImmutable;
    }

    public function id(): ?InvitationId
    {
        return $this->id;
    }

    public function familyId(): FamilyId
    {
        return $this->familyId;
    }

    public function invitedBy(): UserId
    {
        return $this->invitedBy;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function token(): InvitationToken
    {
        return $this->token;
    }

    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
