<?php

declare(strict_types=1);

namespace Packages\Auth\Infrastructure\Repository;

use App\Models\User as EloquentUser;
use Packages\Auth\Domain\Entity\User;
use Packages\Auth\Domain\Repository\UserRepositoryInterface;
use Packages\Auth\Domain\ValueObject\HashedPassword;
use Packages\Auth\Domain\ValueObject\UserName;
use Packages\Shared\ValueObject\Email;
use Packages\Shared\ValueObject\UserId;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User
    {
        $eloquentUser = EloquentUser::where('email', $email->value())->first();

        if ($eloquentUser === null) {
            return null;
        }

        return $this->toDomainEntity($eloquentUser);
    }

    public function save(User $user): User
    {
        $eloquentUser = EloquentUser::create([
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'password' => $user->password()->value(),
        ]);

        return $this->toDomainEntity($eloquentUser);
    }

    private function toDomainEntity(EloquentUser $model): User
    {
        return User::reconstruct(
            new UserId($model->id),
            new UserName($model->name),
            new Email($model->email),
            new HashedPassword($model->password),
        );
    }
}
