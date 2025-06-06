<?php

declare(strict_types=1);

namespace App\Domains\Category\Services;

use App\Domains\Category\CategoryQueries;
use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Category;
use App\Models\CategoryChannelReference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryWebspertService
{
    public function createCategory(Category $category, int $saleChannelId = 0): ?int
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $category = $categoryQueries->refresh($category);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);

        $webhookUrls = [WebhookUrls::CATEGORY_CREATE->value];

        if (0 === $saleChannelId) {
            $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
                $webhookUrls,
                $category->company_id,
                SaleChannelTypes::WEBSPERT_ECOMMERCE->value
            );
        } else {
            $saleChannel = $saleChannelQueries->getByIdAndStatus($saleChannelId);
            $saleChannels = collect();

            if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                $saleChannels = collect([$saleChannel]);
            }
        }

        Log::channel('category_channel_reference')->info('category channel reference webhook category create started', [
            'start time of the webhook call for the category create' => Carbon::now()->format('Y-m-d H:i:s'),
            'category id: ' . $category->getKey(),
        ]);

        $categoryData = [
            'name' => $category->name,
            'parent_id' => 0,
            'existing_id' => null,
            'status' => 'NORMAL',
        ];

        $webspertEcommerceCategoryId = null;

        try {
            foreach ($saleChannels as $saleChannel) {
                $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                    ->firstWhere('webhook_url_type_id', WebhookUrls::CATEGORY_CREATE->value);
                $url = $saleChannelWebhookUrl->url;

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'secretkey' => $saleChannel->secret,
                    ...$categoryData,
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Category in Webspert', [
                        'response' => $responseData,
                    ]);

                    $externalCategoryId = $responseData['data']['category_id'];

                    $categoryChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'category_id' => $category->getKey(),
                        'external_category_id' => $externalCategoryId,
                    ]);

                    $categoryQueries->updateIsAvailableInEcommerce($category);

                    if (SaleChannelTypes::WEBSPERT_ECOMMERCE === $saleChannel->type_id) {
                        $webspertEcommerceCategoryId = $externalCategoryId;
                    }
                } else {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('e_commerce')->info('Response: Error on Category in Webspert', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'category_id' => $category->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }

            if (null !== $webspertEcommerceCategoryId) {
                return $webspertEcommerceCategoryId;
            }
        } catch (Throwable $throwable) {
            Log::channel('category_channel_reference')->error(
                'category channel reference webhook category create failed',
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

        Log::channel('category_channel_reference')->info('category channel reference webhook category create started', [
            'start time of the webhook call for the category create' => Carbon::now()->format('Y-m-d H:i:s'),
            'category id: ' . $category->getKey(),
        ]);

        return null;
    }

    public function updateCategory(Category $category): void
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $category = $categoryQueries->getCategoryById($category->id);

        if (false === $category->is_available_in_ecommerce) {
            return;
        }

        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);
        $categoryChannelReferences = $categoryChannelReferenceQueries->getCategoryIdForWebspert(
            $category->getKey(),
        );

        if (! $categoryChannelReferences instanceof CategoryChannelReference) {
            Log::channel('category_channel_reference')->info('update category : create category call', [
                'start time of the webhook call for the category update' => Carbon::now()->format('Y-m-d H:i:s'),
                'category id: ' . $category->getKey(),
            ]);

            $this->createCategory($category);

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::CATEGORY_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $category->company_id);

        $saleChannels = $saleChannels->where('type_id', SaleChannelTypes::WEBSPERT_ECOMMERCE->value);

        if ($saleChannels->isEmpty()) {
            Log::channel('category_channel_reference')->info('update category : return when sale channels is empty', [
                'start time of the webhook call for the category update' => Carbon::now()->format('Y-m-d H:i:s'),
                'category id: ' . $category->getKey(),
            ]);

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook category update details started', [
            'start time of the webhook call for the category update' => Carbon::now()->format('Y-m-d H:i:s'),
            'category id: ' . $category->getKey(),
        ]);

        $categoryData = [
            'category_id' => $categoryChannelReferences->external_category_id,
            'name' => $category->name,
            'parent_id' => 0,
            'status' => 'NORMAL',
        ];

        foreach ($saleChannels as $saleChannel) {
            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::CATEGORY_UPDATE->value);
            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'secretkey' => $saleChannel->secret,
                ...$categoryData,
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Category in Webspert', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Category in Webspert', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'category_id' => $category->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook category update details ended', [
            'end time of the webhook call for the category update' => Carbon::now()->format('Y-m-d H:i:s'),
            'category id: ' . $category->getKey(),
        ]);
    }
}
