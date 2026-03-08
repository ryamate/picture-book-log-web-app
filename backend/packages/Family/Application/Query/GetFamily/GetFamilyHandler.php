<?php

declare(strict_types=1);

namespace Packages\Family\Application\Query\GetFamily;

use App\Models\Family;

final class GetFamilyHandler
{
    public function handle(GetFamilyQuery $query): Family
    {
        return Family::findOrFail($query->familyId);
    }
}
