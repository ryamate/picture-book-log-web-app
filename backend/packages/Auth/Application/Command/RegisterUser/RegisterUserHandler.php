<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Command\RegisterUser;

use App\Models\User as EloquentUser;
use Illuminate\Support\Facades\Hash;
use Packages\Auth\Domain\Entity\User;
use Packages\Auth\Domain\Repository\UserRepositoryInterface;
use Packages\Auth\Domain\ValueObject\HashedPassword;
use Packages\Auth\Domain\ValueObject\UserName;
use Packages\Shared\ValueObject\Email;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function handle(RegisterUserCommand $command): array
    {
        $email = new Email($command->email);
        $hashedPassword = new HashedPassword(Hash::make($command->password));
        $userName = new UserName($command->name);

        $domainUser = User::createNew($userName, $email, $hashedPassword);
        $savedUser = $this->userRepository->save($domainUser);

        $eloquentUser = EloquentUser::where('email', $command->email)->first();
        $token = $eloquentUser->createToken('auth_token')->plainTextToken;

        return [
            'user' => $savedUser,
            'token' => $token,
        ];
    }
}
