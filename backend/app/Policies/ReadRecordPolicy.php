<?php

namespace App\Policies;

use App\Models\ReadRecord;
use App\Models\User;

class ReadRecordPolicy
{
    public function manage(User $user, ReadRecord $readRecord): bool
    {
        return $user->family_id === $readRecord->family_id;
    }
}
