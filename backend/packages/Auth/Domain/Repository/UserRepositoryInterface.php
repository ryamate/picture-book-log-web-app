<?php

declare(strict_types=1);

namespace Packages\Auth\Domain\Repository;

use Packages\Auth\Domain\Entity\User;
use Packages\Shared\ValueObject\Email;

interface UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User;

    public function save(User $user): User;
}
