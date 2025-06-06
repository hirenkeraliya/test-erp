<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class LogPosApiCalls
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response|RedirectResponse) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() === 'POST' &&
            ($request->headers->get('authorization') || $request->has('device_type') || $request->has('grant_type'))
        ) {
            Log::channel('pos_api')->info(json_encode([
                'url' => $request->fullUrl(),
                'request-details' => $request->all(),
                'request_headers' => $request->headers->all(),
                'user' => $request->user()?->toArray(),
                'response' => $response->getContent(),
                'response_status' => $response->getStatusCode(),
                'response_headers' => $response->headers->all(),
            ], JSON_THROW_ON_ERROR));
        }

        return $response;
    }
}
