<?php

declare(strict_types=1);

namespace Packages\Family\Infrastructure\Repository;

use App\Models\FamilyInvitation as EloquentFamilyInvitation;
use DateTimeImmutable;
use Packages\Family\Domain\Entity\Invitation;
use Packages\Family\Domain\Repository\InvitationRepositoryInterface;
use Packages\Family\Domain\ValueObject\InvitationId;
use Packages\Family\Domain\ValueObject\InvitationToken;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\FamilyId;
use Packages\Shared\ValueObject\UserId;

final class EloquentInvitationRepository implements InvitationRepositoryInterface
{
    public function findById(InvitationId $id): ?Invitation
    {
        $model = EloquentFamilyInvitation::find($id->value());

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByToken(InvitationToken $token): ?Invitation
    {
        $model = EloquentFamilyInvitation::where('token', $token->value())->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findPendingByFamilyIdAndEmail(FamilyId $familyId, Email $email): ?Invitation
    {
        $model = EloquentFamilyInvitation::where('family_id', $familyId->value())
            ->where('email', $email->value())
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    /** @return Invitation[] */
    public function findByFamilyId(FamilyId $familyId): array
    {
        return EloquentFamilyInvitation::where('family_id', $familyId->value())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (EloquentFamilyInvitation $model) => $this->toDomainEntity($model))
            ->all();
    }

    public function save(Invitation $invitation): Invitation
    {
        if ($invitation->id() === null) {
            $model = EloquentFamilyInvitation::create([
                'family_id' => $invitation->familyId()->value(),
                'invited_by' => $invitation->invitedBy()->value(),
                'email' => $invitation->email()->value(),
                'token' => $invitation->token()->value(),
                'accepted_at' => $invitation->acceptedAt(),
                'expires_at' => $invitation->expiresAt(),
            ]);
        } else {
            $model = EloquentFamilyInvitation::findOrFail($invitation->id()->value());
            $model->update([
                'accepted_at' => $invitation->acceptedAt(),
            ]);
        }

        return $this->toDomainEntity($model);
    }

    public function delete(InvitationId $id): void
    {
        EloquentFamilyInvitation::destroy($id->value());
    }

    private function toDomainEntity(EloquentFamilyInvitation $model): Invitation
    {
        return Invitation::reconstruct(
            new InvitationId($model->id),
            new FamilyId($model->family_id),
            new UserId($model->invited_by),
            new Email($model->email),
            InvitationToken::fromString($model->token),
            $model->accepted_at ? new DateTimeImmutable($model->accepted_at->toISOString()) : null,
            new DateTimeImmutable($model->expires_at->toISOString()),
        );
    }
}
