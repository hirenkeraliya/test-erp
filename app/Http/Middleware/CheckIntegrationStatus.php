<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use Closure;
use Illuminate\Http\Request;

class CheckIntegrationStatus
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Integration $integration */
        $integration = $request->user();

        if (! $integration->status) {
            abort(412, 'Your account is inactive, Please contact the super admin');
        }

        return $next($request);
    }
}
