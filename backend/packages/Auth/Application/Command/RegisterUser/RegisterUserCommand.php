<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Command\RegisterUser;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
