<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\ListInvitations;

use App\Models\FamilyInvitation;
use Illuminate\Database\Eloquent\Collection;

final class ListInvitationsHandler
{
    /** @return Collection<int, FamilyInvitation> */
    public function handle(ListInvitationsQuery $query): Collection
    {
        return FamilyInvitation::where('family_id', $query->familyId)
            ->with('invitedByUser')
            ->orderByDesc('created_at')
            ->get();
    }
}
