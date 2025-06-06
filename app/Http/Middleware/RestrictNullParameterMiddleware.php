<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictNullParameterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()) {
            $routeParameters = $request->route()->parameters();

            foreach ($routeParameters as $param => $value) {
                if (is_array($value)) {
                    continue;
                }

                if ((string) $value !== 'null') {
                    continue;
                }

                return response(sprintf("Route parameter '%s' cannot be null.", $param), 400);
            }
        }

        return $next($request);
    }
}
