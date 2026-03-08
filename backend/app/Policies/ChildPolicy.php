<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\User;

class ChildPolicy
{
    public function manage(User $user, Child $child): bool
    {
        return $user->family_id === $child->family_id;
    }
}
