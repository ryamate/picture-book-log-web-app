<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\ListMembers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListMembersHandler
{
    /** @return Collection<int, User> */
    public function handle(ListMembersQuery $query): Collection
    {
        return User::where('family_id', $query->familyId)->get();
    }
}
