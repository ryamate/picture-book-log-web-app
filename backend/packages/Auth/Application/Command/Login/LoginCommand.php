<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Command\Login;

final class LoginCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
