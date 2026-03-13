<?php

namespace App\Policies;

use App\Models\PictureBook;
use App\Models\User;

/**
 * Authorization policy for PictureBook actions.
 *
 * Ensures users can only manage picture books belonging to their own family.
 */
class PictureBookPolicy
{
    /**
     * Determine if the user can view, update, or delete the picture book.
     *
     * Authorized when the user belongs to the same family as the picture book.
     *
     * @param User $user
     * @param PictureBook $pictureBook
     * @return bool
     */
    public function manage(User $user, PictureBook $pictureBook): bool
    {
        return $user->family_id === $pictureBook->family_id;
    }
}
