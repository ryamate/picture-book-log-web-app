<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Packages\Family\Domain\Exception\DuplicateInvitationException;
use Packages\Family\Domain\Exception\InvitationAlreadyAcceptedException;
use Packages\Family\Domain\Exception\InvitationExpiredException;
use Packages\Family\Domain\Exception\UserAlreadyInFamilyException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvitationExpiredException $e) {
            return response()->json(['message' => 'この招待は期限切れです。'], 410);
        });

        $exceptions->render(function (InvitationAlreadyAcceptedException $e) {
            return response()->json(['message' => 'この招待は既に受理されています。'], 409);
        });

        $exceptions->render(function (UserAlreadyInFamilyException $e) {
            return response()->json(['message' => 'このユーザーは既に家族に所属しています。'], 409);
        });

        $exceptions->render(function (DuplicateInvitationException $e) {
            return response()->json(['message' => 'このメールアドレスへの招待は既に送信されています。'], 409);
        });
    })->create();
