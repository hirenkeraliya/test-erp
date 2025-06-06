<?php

namespace App\Services;

use App\Domains\Azentio\Enums\AzentioUrls;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AzentioOneerpService
{
    private string $baseUrl = '';

    private string $authenticationApiKey = '';

    private const TOKEN_CACHE_EXPIRATION_MINUTES = 59;

    private string $fromDate = '';

    private string $toDate = '';

    public function setDetails(
        string $baseUrl,
        string $authenticationApiKey,
        string $fromDate,
        string $toDate,
    ): void {
        $this->baseUrl = $baseUrl;
        $this->authenticationApiKey = $authenticationApiKey;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function authenticate(): Response
    {
        $url = $this->baseUrl.AzentioUrls::LOGIN_URL->value;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-tenanttype' => 'live',
        ];

        try {
            $response = Http::retry(times: 3)
                ->timeout(seconds: 30)
                ->withHeaders($headers)->post($url, [
                    'apiKey' => $this->authenticationApiKey,
                ]);

            Log::channel('azentio_oneerp_integration')->info('Oneerp authentication attempt', [
                'url' => $url,
                'status_code' => $response->status(),
                'headers' => $headers,
                'success' => $response->successful(),
                'response' => $this->maskSensitiveData($response->json()),
            ]);

            return $response;
        } catch (Throwable $throwable) {
            Log::channel('azentio_oneerp_integration')->error('Oneerp authentication exception', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
                'url' => $url,
            ]);

            throw $throwable;
        }
    }

    private function getAuthToken(): string
    {
        return Cache::remember('azentio_oneerp_token', self::TOKEN_CACHE_EXPIRATION_MINUTES * 60, function () {
            $response = $this->authenticate();
            if (! $response->successful()) {
                throw new Exception('Failed to authenticate with OneERP');
            }

            return $response->json('token');
        });
    }

    public function getItems(int $rowNumFrom = 1, int $rowNumTo = 100): Response
    {
        try {
            $token = $this->getAuthToken();
            $payload = [
                'filter' => [
                    'FROM_DT' => $this->fromDate,
                    'TO_DT' => $this->toDate,
                    'rownum_fm' => (string) $rowNumFrom,
                    'rownum_to' => (string) $rowNumTo,
                ],
                'M_COMP_CODE' => 'DIST',
                'M_USER_ID' => 'SYSADMIN',
                'APICODE' => 'ITEMJSON',
                'M_LANG_CODE' => 'ENG',
            ];

            $url = $this->baseUrl.AzentioUrls::GET_API_URL->value;

            $response = Http::retry(times: 3)
                ->timeout(seconds: 30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])->post($url, $payload);

            Log::channel('azentio_oneerp_integration')->info('Oneerp items fetch attempt', [
                'url' => $url,
                'status_code' => $response->status(),
                'success' => $response->successful(),
                'rows' => sprintf('%d-%d', $rowNumFrom, $rowNumTo),
                'payload' => $payload,
                'response' => $response->json(),
            ]);

            return $response;
        } catch (Exception $exception) {
            Log::channel('azentio_oneerp_integration')->error('Oneerp items fetch exception', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'rows' => sprintf('%d-%d', $rowNumFrom, $rowNumTo),
            ]);

            throw $exception;
        }
    }

    public function getMembers(int $rowNumFrom = 1, int $rowNumTo = 100): Response
    {
        try {
            $token = $this->getAuthToken();
            $payload = [
                'filter' => [
                    'FROM_DT' => $this->fromDate,
                    'TO_DT' => $this->toDate,
                    'rownum_fm' => (string) $rowNumFrom,
                    'rownum_to' => (string) $rowNumTo,
                ],
                'M_COMP_CODE' => 'DIST',
                'M_USER_ID' => 'SYSADMIN',
                'APICODE' => 'CUSTOMERJSON',
                'M_LANG_CODE' => 'ENG',
            ];

            $url = $this->baseUrl.AzentioUrls::GET_API_URL->value;

            $response = Http::retry(times: 3)
                ->timeout(seconds: 30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])->post($url, $payload);

            Log::channel('azentio_oneerp_integration')->info('Oneerp members fetch attempt', [
                'url' => $url,
                'status_code' => $response->status(),
                'success' => $response->successful(),
                'rows' => sprintf('%d-%d', $rowNumFrom, $rowNumTo),
                'payload' => $payload,
                'response' => $response->json(),
            ]);

            return $response;
        } catch (Exception $exception) {
            Log::channel('azentio_oneerp_integration')->error('Oneerp members fetch exception', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'rows' => sprintf('%d-%d', $rowNumFrom, $rowNumTo),
            ]);

            throw $exception;
        }
    }

    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['token'];

        return array_map(function ($value, $key) use ($sensitiveKeys) {
            if (in_array($key, $sensitiveKeys)) {
                return '****';
            }

            return $value;
        }, $data, array_keys($data));
    }
}
