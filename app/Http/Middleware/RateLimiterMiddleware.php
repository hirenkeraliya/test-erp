<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Common\Enums\ModelMapping;
use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RateLimiterMiddleware extends ThrottleRequests
{
    protected function handleRequest($request, Closure $next, array $limits): Response
    {
        /** @var User $user */
        $user = $request->user();

        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                Log::channel('job_failure_slack')->info('Rate Limiter', [
                    'Attempts' => $this->limiter->attempts($limit->key) + 1,
                    'URL' => $request->url(),
                    'IP' => $request->ip(),
                    'User' => $user->username ?? null,
                ]);
            }

            $this->limiter->hit($limit->key, $limit->decayMinutes * 60);
        }

        $response = $next($request);

        foreach ($limits as $limit) {
            $response = $this->addHeaders(
                $response,
                $limit->maxAttempts,
                $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts)
            );
        }

        return $response;
    }

    protected function resolveRequestSignature($request): string
    {
        if ($user = $request->user()) {
            $route = $request->route();
            $class = ModelMapping::getCaseName($user::class);

            return sha1($route?->getDomain() . $route?->uri . '|' . $class . '|'. $user->id . '|' . $request->ip());
        }

        if ($route = $request->route()) {
            return sha1($route->getDomain() . $route->uri . '|' . $request->ip());
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }
}
