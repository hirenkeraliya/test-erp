<?php

namespace App\Http\Middleware;

use App\Domains\Common\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;

class EnsureTwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $twoFactorService = resolve(TwoFactorService::class);
        $guard = $twoFactorService->getActiveGuard($request);
        $user = $twoFactorService->getAuthUser($request);

        if (! $guard || ! $user) {
            return redirect('/login');
        }

        if ($user->two_factor_secret && ! $request->session()->get($guard . '_two_factor_authenticated')) {
            return to_route($guard . '.2fa.show_validation_page');
        }

        return $next($request);
    }
}
