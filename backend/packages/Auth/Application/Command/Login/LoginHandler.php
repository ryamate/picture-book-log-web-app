<?php

declare(strict_types=1);

namespace Packages\Auth\Application\Command\Login;

use App\Models\User as EloquentUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Packages\Auth\Domain\Repository\UserRepositoryInterface;
use Packages\Shared\ValueObject\Email;

final class LoginHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return array{user: \Packages\Auth\Domain\Entity\User, token: string}
     */
    public function handle(LoginCommand $command): array
    {
        $email = new Email($command->email);
        $domainUser = $this->userRepository->findByEmail($email);

        if ($domainUser === null) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $eloquentUser = EloquentUser::where('email', $command->email)->first();

        if (! Hash::check($command->password, $eloquentUser->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $eloquentUser->createToken('auth_token')->plainTextToken;

        return [
            'user' => $domainUser,
            'token' => $token,
        ];
    }
}
