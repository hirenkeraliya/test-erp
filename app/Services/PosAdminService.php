<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PosAdminService
{
    public function isEnabled(): bool
    {
        return config('services.pos_admin.url')
            && config('services.pos_admin.company_id')
            && config('services.pos_admin.client_id')
            && config('services.pos_admin.client_secret');
    }

    public function getToken(): ?string
    {
        if (Cache::has('pos_admin_access_token')) {
            return Cache::get('pos_admin_access_token');
        }

        return $this->createToken();
    }

    public function getAppReleases(): array
    {
        $responseData = [
            'status' => false,
            'response_data' => null,
            'status_code' => null,
        ];

        try {
            $client = new Client();

            $queryParams = http_build_query([
                'company_id' => config('services.pos_admin.company_id'),
            ]);

            $url = config('services.pos_admin.url') . '/api/app-releases?' . $queryParams;

            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . Cache::get('pos_admin_access_token'),
                ],
            ]);

            $responseData['status'] = $response->getStatusCode() === 200;
            $responseData['status_code'] = $response->getStatusCode();

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR);
                $responseData['response_data'] = $body;
            }

            return $responseData;
        } catch (Throwable $throwable) {
            Log::channel('pos_admin')->error('App releases failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
            ]);

            $responseData['status'] = false;
            $responseData['status_code'] = $throwable->getCode();
            throw new Exception($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    public function updateCompanyDetails(array $records): void
    {
        try {
            Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . Cache::get('pos_admin_access_token'),
            ])->timeout(config('services.http_time_out'))->post(
                config('services.pos_admin.url') . '/api/update-external-company-details',
                [
                    'data' => json_encode($records),
                ]
            )->throw();
        } catch (Throwable $throwable) {
            Log::channel('pos_admin')->error('Share Company details add/update to POS Admin failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            throw new Exception($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    public function updateAllCounterDetails(array $records): void
    {
        try {
            Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . Cache::get('pos_admin_access_token'),
            ])->timeout(config('services.http_time_out'))->post(
                config('services.pos_admin.url') . '/api/update-external-counters-details',
                [
                    'data' => json_encode($records),
                ]
            )->throw();
        } catch (Throwable $throwable) {
            Log::channel('pos_admin')->error('Share Counters details add/update to POS Admin failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            throw new Exception($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    public function removeTokenFromCache(): void
    {
        Cache::forget('pos_admin_access_token');
    }

    private function createToken(): ?string
    {
        $client = new Client();

        try {
            $response = $client->post(config('services.pos_admin.url') . '/oauth/token', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.pos_admin.client_id'),
                    'client_secret' => config('services.pos_admin.client_secret'),
                ],
            ]);

            $status = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR);

            if (200 === $status) {
                Cache::put('pos_admin_access_token', $body->access_token);

                return $body->access_token;
            }

            Log::channel('pos_admin')->error('login failed on pos admin on response', [
                'Error' => $body,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('pos_admin')->error('login failed on pos admin', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return (string) null;
    }
}
