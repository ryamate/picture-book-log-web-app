<?php

namespace App\Policies;

use App\Models\PictureBook;
use App\Models\User;

class PictureBookPolicy
{
    public function manage(User $user, PictureBook $pictureBook): bool
    {
        return $user->family_id === $pictureBook->family_id;
    }
}
