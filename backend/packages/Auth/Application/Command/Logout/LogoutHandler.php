<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Command\Logout;

final class LogoutHandler
{
    public function handle(LogoutCommand $command): void
    {
        $command->user->currentAccessToken()->delete();
    }
}
