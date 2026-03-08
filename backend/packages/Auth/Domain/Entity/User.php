<?php

declare(strict_types=1);

namespace Packages\Auth\Domain\Entity;

use Packages\Auth\Domain\ValueObject\HashedPassword;
use Packages\Auth\Domain\ValueObject\UserName;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\UserId;

final class User
{
    private function __construct(
        private readonly ?UserId $id,
        private readonly UserName $name,
        private readonly Email $email,
        private readonly HashedPassword $password,
    ) {}

    public static function createNew(
        UserName $name,
        Email $email,
        HashedPassword $password,
    ): self {
        return new self(null, $name, $email, $password);
    }

    public static function reconstruct(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $password,
    ): self {
        return new self($id, $name, $email, $password);
    }

    public function id(): ?UserId
    {
        return $this->id;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }
}
