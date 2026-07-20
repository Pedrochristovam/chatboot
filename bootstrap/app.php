<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $error = static fn (string $message, string $code, string $type, int $status) => response()->json([
            'message' => $message,
            'error' => [
                'code' => $code,
                'type' => $type,
            ],
        ], $status);

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($error) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $error('Não autenticado.', 'unauthenticated', 'authentication', 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($error) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $error(
                $exception->getMessage() ?: 'Você não tem permissão para acessar este recurso.',
                'forbidden',
                'authorization',
                403,
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($error) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $error('Recurso não encontrado.', 'resource_not_found', 'domain', 404);
        });

        $exceptions->render(function (DomainException $exception, Request $request) use ($error) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $error($exception->getMessage(), 'domain_error', 'domain', 422);
        });
    })->create();
