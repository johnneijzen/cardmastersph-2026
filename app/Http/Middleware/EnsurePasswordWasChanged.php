<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces an account holder to choose their own password.
 *
 * When an administrator resets a password the account is flagged. The holder
 * may only reach the password settings until the flag is cleared.
 */
class EnsurePasswordWasChanged
{
    /**
     * The routes the holder may reach while the flag is set.
     *
     * @var array<int, string>
     */
    protected array $allowedRoutes = [
        'security.edit',
        'password.confirm',
        'password.confirm.store',
        'logout',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(...$this->allowedRoutes)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your password must be changed before you can continue.',
            ], 403);
        }

        return redirect()->route('security.edit');
    }
}
