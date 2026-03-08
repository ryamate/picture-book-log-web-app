<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Command\Logout;

use App\Models\User as EloquentUser;

final class LogoutCommand
{
    public function __construct(
        public readonly EloquentUser $user,
    ) {}
}
