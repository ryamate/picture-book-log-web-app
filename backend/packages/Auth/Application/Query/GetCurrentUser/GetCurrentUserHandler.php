<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Query\GetCurrentUser;

use App\Models\User as EloquentUser;

final class GetCurrentUserHandler
{
    public function handle(GetCurrentUserQuery $query): EloquentUser
    {
        return EloquentUser::findOrFail($query->userId);
    }
}
