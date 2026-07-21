<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Você não tem permissão para acessar este recurso.');
        }

        $required = collect($permissions)
            ->flatMap(fn (string $item) => explode(',', $item))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values();

        $allowed = $required->contains(fn (string $permission) => $user->hasPermission($permission));

        if (! $allowed) {
            abort(403, 'Você não tem permissão para acessar este recurso.');
        }

        return $next($request);
    }
}
