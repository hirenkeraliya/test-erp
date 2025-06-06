<?php

namespace App\Http\Middleware;

use App\CommonFunctions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLogging
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = Str::uuid()->toString();

        Log::channel('request_logging')->info('Request', [
            'url' => $request->fullUrl(),
            'requestId' => $requestId,
            'method' => $request->method(),
            'memory' => memory_get_usage(),
            'data' => $request->all(),
        ]);

        $response = $next($request);

        $memoryUsageBytes = memory_get_peak_usage();
        $memoryUsageMB = CommonFunctions::numberFormatString($memoryUsageBytes / (1024 * 1024)); // Bytes to MB;

        Log::channel('request_logging')->info('Response', [
            'url' => $request->fullUrl(),
            'requestId' => $requestId,
            'memory' => $memoryUsageMB . 'MB',
            'data' => str_contains(
                $response->headers->get('content-type'),
                'text/html'
            ) ? 'HTML' : $response->getContent(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
