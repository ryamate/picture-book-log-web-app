<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\Auth\Application\Command\Login\LoginCommand;
use Packages\Auth\Application\Command\Login\LoginHandler;
use Packages\Auth\Application\Command\Logout\LogoutCommand;
use Packages\Auth\Application\Command\Logout\LogoutHandler;
use Packages\Auth\Application\Command\RegisterUser\RegisterUserCommand;
use Packages\Auth\Application\Command\RegisterUser\RegisterUserHandler;
use Packages\Auth\Application\Query\GetCurrentUser\GetCurrentUserHandler;
use Packages\Auth\Application\Query\GetCurrentUser\GetCurrentUserQuery;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserHandler $handler): JsonResponse
    {
        $result = $handler->handle(new RegisterUserCommand(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        ));

        return response()->json([
            'user' => new UserResource(
                \App\Models\User::where('email', $request->validated('email'))->first()
            ),
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginRequest $request, LoginHandler $handler): JsonResponse
    {
        $result = $handler->handle(new LoginCommand(
            email: $request->validated('email'),
            password: $request->validated('password'),
        ));

        return response()->json([
            'user' => new UserResource(
                \App\Models\User::where('email', $request->validated('email'))->first()
            ),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request, LogoutHandler $handler): JsonResponse
    {
        $handler->handle(new LogoutCommand(user: $request->user()));

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request, GetCurrentUserHandler $handler): JsonResponse
    {
        $user = $handler->handle(new GetCurrentUserQuery(
            userId: $request->user()->id,
        ));

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}
