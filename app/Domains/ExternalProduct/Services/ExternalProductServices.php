<?php

namespace App\Domains\ExternalProduct\Services;

use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReferenceCategory\ProductChannelReferenceCategoryQueries;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalProductServices
{
    private const CONTENT_TYPE = 'application/json';

    private const PRODUCT_DETAIL_ENDPOINT = 'product/search_product_detail';

    public function fetchExternalProduct(SaleChannel $saleChannel, Collection $externalProducts): void
    {
        $productQueries = resolve(ProductQueries::class);

        $externalProducts->chunk(100)->each(function ($chunkedProducts) use ($saleChannel, $productQueries): void {
            foreach ($chunkedProducts as $externalProduct) {
                $externalProductId = $externalProduct->id;
                $upc = $productQueries->getUpcById((int) $externalProduct->product_id);

                $this->logInfo('Processing external product', [
                    'start_time' => Carbon::now()->format('Y-m-d H:i:s'),
                    'external_product_id' => $externalProductId,
                ]);

                $this->retryWithDelay(function () use ($saleChannel, $upc, $externalProductId): void {
                    $this->processExternalProduct($saleChannel, $upc, $externalProductId);
                }, 3, 200);
            }
        });
    }

    private function retryWithDelay(callable $callback, int $maxRetries, int $delayMs): void
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $callback();

                return;
            } catch (Throwable $e) {
                $attempts++;
                if ($attempts >= $maxRetries) {
                    throw $e;
                }

                usleep($delayMs * 1000);
            }
        }
    }

    private function processExternalProduct(SaleChannel $saleChannel, string $upc, int $externalProductId): void
    {
        $url = $saleChannel->url . self::PRODUCT_DETAIL_ENDPOINT;

        try {
            $response = $this->makeHttpRequest($url, [
                'secretkey' => $saleChannel->secret,
                'barcode' => $upc,
            ]);

            if ($response->successful()) {
                $responseData = $this->parseResponse($response);

                if ('Success' !== $responseData['code_title'] || empty($responseData['data'])) {
                    $this->logErrorResponse($response, $externalProductId);

                    return;
                }

                $this->logInfo('Response: Products in Webspert', [
                    'response' => $responseData,
                ]);
                $this->updateExternalProductCategories($responseData['data']['products'], $externalProductId);
            } else {
                $this->logErrorResponse($response, $externalProductId);
            }
        } catch (Throwable $throwable) {
            $this->logError('Error on Fetching Categories From Product in Webspert', [
                'external_product_id' => $externalProductId,
                'error_message' => $throwable->getMessage(),
            ]);
            throw $throwable;
        }
    }

    private function makeHttpRequest(string $url, array $data): Response
    {
        return Http::withHeaders([
            'Content-Type' => self::CONTENT_TYPE,
            'Accept' => self::CONTENT_TYPE,
        ])
            ->timeout(config('services.http_time_out'))
            ->connectTimeout(config('services.http_time_out'))
            ->post($url, $data);
    }

    private function parseResponse(Response $response): array
    {
        return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function updateExternalProductCategories(array $externalProducts, int $externalProductId): void
    {
        $categoryQueries = resolve(ProductChannelReferenceCategoryQueries::class);

        foreach ($externalProducts as $externalProduct) {
            foreach ($externalProduct['categories'] as $category) {
                $categoryQueries->addExternalCategoryId($category['category_id'], $externalProductId);
            }
        }
    }

    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('product_channel_reference_categories')->info($message, $context);
    }

    private function logError(string $message, array $context = []): void
    {
        Log::channel('product_channel_reference_categories')->error($message, $context);
    }

    private function logErrorResponse(Response $response, int $externalProductId): void
    {
        $this->logInfo('Error on Fetching Categories From Product in Webspert', [
            'status_code' => $response->status(),
            'response_body' => $response->body() ?: 'No response body provided',
            'request_data' => [
                'external_product_id' => $externalProductId,
            ],
        ]);
    }
}
