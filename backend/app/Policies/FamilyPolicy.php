<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    public function view(User $user, Family $family): bool
    {
        return $user->family_id === $family->id;
    }

    public function update(User $user, Family $family): bool
    {
        return $user->family_id === $family->id;
    }
}
