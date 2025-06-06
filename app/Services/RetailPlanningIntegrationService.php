<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RetailPlanningIntegrationService
{
    public function sendResponse(array $data, string $url, string $secret): void
    {
        try {
            $response = Http::retry(times: 3, sleepMilliseconds: 100)
                ->withHeaders([
                    'RETAIL-PLANNING-SECRET' => $secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $data);

            Log::channel('retail_planning')->info('[Retail Planning] Request and Response', [
                'request_data' => $data,
                'url' => $url,
                'response_body' => $response->body(),
                'status' => $response->status(),
            ]);
        } catch (RequestException $requestException) {
            Log::channel('retail_planning')->error('[Retail Planning] Failed - Connection error', [
                'message' => $requestException->getMessage(),
                'body' => $requestException->response->body(),
            ]);
        } catch (Throwable $throwable) {
            Log::channel('retail_planning')->error('[Retail Planning] Failed - Unexpected error', [
                'Error message' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }
    }
}
