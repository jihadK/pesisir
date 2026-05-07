<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated'], 401)
                : redirect()->route('login');
        }

        if (! $user->hasPermission($permission)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden: missing permission ' . $permission], 403)
                : abort(403, 'Anda tidak punya akses: ' . $permission);
        }

        return $next($request);
    }
}
