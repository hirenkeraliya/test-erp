<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\ExternalConnection\ExternalConnectionQueries;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyExternalConnectionToken
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $token = $request->input('token');

        if (! $token) {
            return response()->json([
                'error' => 'Token missing',
            ], 401);
        }

        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);

        if (! $externalConnectionQueries->existsByToken($request->token)) {
            return response()->json([
                'error' => 'The provided token does not match any records.',
            ], 412);
        }

        Log::channel('external_connection_api')->info(json_encode([
            'url' => $request->fullUrl(),
            'request-details' => $request->all(),
            'request_headers' => $request->headers->all(),
            'user' => $request->user()?->toArray(),
            'response' => $response->getContent(),
            'response_status' => $response->getStatusCode(),
            'response_headers' => $response->headers->all(),
        ], JSON_THROW_ON_ERROR));

        return $response;
    }
}
