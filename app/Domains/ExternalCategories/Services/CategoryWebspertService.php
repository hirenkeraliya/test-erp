<?php

declare(strict_types=1);

namespace App\Domains\ExternalCategories\Services;

use App\Domains\ExternalCategories\Enums\ExternalCategoriesEnum;
use App\Domains\ExternalCategories\ExternalCategoryQueries;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryWebspertService
{
    public function fetchCategories(SaleChannel $saleChannel): void
    {
        $externalCategoriesQueries = resolve(ExternalCategoryQueries::class);

        Log::channel('category_channel_reference')->info('category channel reference webhook category fetch started', [
            'start time of the webhook call for the category fetch' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $url = $saleChannel->url . ExternalCategoriesEnum::GET_CATEGORY_LIST_URL->value;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
            ]);
            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('external_categories')->info('Response: Category in Webspert', [
                    'response' => $responseData,
                ]);

                $externalCategories = $responseData['data']['categories'];
                foreach ($externalCategories as $category) {
                    $parentId = $this->getParentCategoryId($category['parent_id']);

                    $externalCategoryData = [
                        'name' => $category['category_name'],
                        'parent_category_id' => $parentId,
                        'company_id' => $saleChannel->company_id,
                        'sale_channel_id' => $saleChannel->getKey(),
                        'external_category_id' => $category['category_id'],
                    ];

                    $externalCategoriesQueries->addNew($externalCategoryData);
                }
            } else {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('external_categories')->info('Response: Error on Category in Webspert', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        } catch (Throwable $throwable) {
            Log::channel('external_categories')->error(
                'external category webhook category fetch failed',
                [
                    'Error message' => $throwable->getMessage(),
                    'Error code' => $throwable->getCode(),
                    'File' => $throwable->getFile(),
                    'Line' => $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]
            );
        }

        Log::channel('category_channel_reference')->info('category channel reference webhook category fetch started', [
            'start time of the webhook call for the category fetch' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function getParentCategoryId(int $parentId): int
    {
        $externalCategoriesQueries = resolve(ExternalCategoryQueries::class);

        return $externalCategoriesQueries->getParentCategoryId($parentId);
    }
}
