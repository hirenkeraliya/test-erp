<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencyRatesUpdateService
{
    public function getCurrencyRate(string $baseCurrencyCode): array
    {
        $responseData = [
            'status' => false,
            'status_code' => null,
            'response_data' => null,
        ];

        $url = config('services.exchanges_rates.exchanges_rate_api_url').'/'.$baseCurrencyCode;
        $token = config('services.exchanges_rates.exchanges_rate_api_key');

        try {
            $response = Http::withToken($token)->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->get($url);

            $responseData['status'] = true;
            $responseData['status_code'] = $response->status();

            $responseData['response_data'] = json_decode((string) $response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            Log::error('currency_rate_update_service', [
                'Currency Rate Update Service Failed Response' => $throwable->getMessage(),
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return $responseData;
    }
}
