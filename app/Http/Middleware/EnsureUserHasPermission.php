<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route gate: caller passes a list of permission keys. Admins are always
 * allowed through; subadmins must hold AT LEAST ONE of the listed permissions.
 *
 * Usage:
 *     Route::middleware('admin.permission:news_write,news_publish')->group(...)
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        foreach ($permissions as $key) {
            if ($user->hasPermission($key)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
