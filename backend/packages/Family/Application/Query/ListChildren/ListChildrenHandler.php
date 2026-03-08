<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\ListChildren;

use App\Models\Child;
use Illuminate\Database\Eloquent\Collection;

final class ListChildrenHandler
{
    /** @return Collection<int, Child> */
    public function handle(ListChildrenQuery $query): Collection
    {
        return Child::where('family_id', $query->familyId)->get();
    }
}
