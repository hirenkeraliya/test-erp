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

class CategoryEcommerceService
{
    public function createCategory(Category $category, int $saleChannelId = 0): ?int
    {
        if (false === $category->is_available_in_ecommerce) {
            Log::channel('category_channel_reference')->info(
                'Create category : return when category is not available in ecommerce',
                [
                    'start time of the webhook call for the category create' => Carbon::now()->format('Y-m-d H:i:s'),
                    'category id: ' . $category->getKey(),
                ]
            );

            return null;
        }

        $categoryQueries = resolve(CategoryQueries::class);
        $category = $categoryQueries->refresh($category);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);

        $webhookUrls = [WebhookUrls::CATEGORY_CREATE->value];

        if (0 === $saleChannelId) {
            $saleChannels = $saleChannelQueries->getSaleChannelsByCompanyAndTypeId(
                $webhookUrls,
                $category->company_id,
                SaleChannelTypes::ECOMMERCE->value
            );
        } else {
            $saleChannel = $saleChannelQueries->getByIdAndStatus($saleChannelId);
            $saleChannels = collect();

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $saleChannels = collect([$saleChannel]);
            }
        }

        if ($saleChannels->isEmpty()) {
            Log::channel('category_channel_reference')->info('category create : sale channels is empty', [
                'start time of the webhook call for the category create' => Carbon::now()->format('Y-m-d H:i:s'),
                'category id: ' . $category->getKey(),
            ]);

            return null;
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

        try {
            foreach ($saleChannels as $saleChannel) {
                $categoryData['parent_id'] = null;

                if ($category->parent_category_id) {
                    $categoryChannelReference = $categoryChannelReferenceQueries->getExternalCategoryIdFromCategoryId(
                        $category->parent_category_id
                    );
                    if ($categoryChannelReference instanceof CategoryChannelReference) {
                        $categoryData['parent_id'] = $categoryChannelReference->external_category_id;
                    }
                }

                $categoryData = $this->preparedRecord($category, $categoryData);

                $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                    ->firstWhere('webhook_url_type_id', WebhookUrls::CATEGORY_CREATE->value);

                $url = $saleChannelWebhookUrl->url;

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, $categoryData);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('category_channel_reference')->info('Response: Add New Category', [
                        'response' => $responseData,
                    ]);

                    $externalCategoryId = $responseData['data']['category_id'];

                    $categoryChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->getKey(),
                        'category_id' => $category->getKey(),
                        'external_category_id' => $externalCategoryId,
                    ]);

                    $categoryQueries->updateIsAvailableInEcommerce($category);

                    return $externalCategoryId;
                }

                Log::channel('category_channel_reference')->info('Response: Error on Banner in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'category_id' => $category->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
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
        $category = $this->fetchCategoryRecords($category->id);

        if (false === $category->is_available_in_ecommerce) {
            Log::channel('category_channel_reference')->info(
                'Update category : return when category is not available in ecommerce',
                [
                    'start time of the webhook call for the category update' => Carbon::now()->format('Y-m-d H:i:s'),
                    'category id: ' . $category->getKey(),
                ]
            );

            return;
        }

        $categoryChannelReference = $this->fetchCategoryChannelReference($category->getKey());

        if (! $categoryChannelReference instanceof CategoryChannelReference) {
            Log::channel('category_channel_reference')->info('Update category : create category call', [
                'start time of the webhook call for the category update' => Carbon::now()->format('Y-m-d H:i:s'),
                'category id: ' . $category->getKey(),
            ]);

            $this->createCategory($category);

            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::CATEGORY_UPDATE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $category->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('category_channel_reference')->info('Update category : return when sale channels is empty', [
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
            'category_id' => $categoryChannelReference->external_category_id,
            'name' => $category->name,
            'parent_id' => 0,
            'status' => 'NORMAL',
        ];

        foreach ($saleChannels as $saleChannel) {
            $categoryData = $this->preparedRecord($category, $categoryData);
            $categoryData['existing_id'] = $categoryChannelReference->external_category_id;

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
            ->firstWhere('webhook_url_type_id', WebhookUrls::CATEGORY_UPDATE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, $categoryData);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Category in E-Commerce', [
                    'response' => $responseData,
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Category in E-Commerce', [
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

    public function unAvailableCategoryInCommerce(int $categoryId): void
    {
        $category = $this->fetchCategoryRecords($categoryId);
        $categoryChannelReference = $this->fetchCategoryChannelReference($category->getKey());

        if (! $categoryChannelReference instanceof CategoryChannelReference) {
            Log::channel('category_channel_reference')->info('unavailable category : create category call', [
                'start time of the webhook call for the category unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
                'category id: ' . $category->getKey(),
            ]);

            $this->createCategory($category);

            return;
        }

        $webhookUrls = [WebhookUrls::CATEGORY_UNAVAILABLE->value];

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $category->company_id);

        if ($saleChannels->isEmpty()) {
            Log::channel('category_channel_reference')->info(
                'Unavailable category : return when sale channels is empty',
                [
                    'start time of the webhook call for the category unavailable' => Carbon::now()->format(
                        'Y-m-d H:i:s'
                    ),
                    'category id: ' . $category->getKey(),
                ]
            );

            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook category unavailable details started', [
            'start time of the webhook call for the category unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'category id: ' . $category->getKey(),
        ]);

        foreach ($saleChannels as $saleChannel) {
            if ($saleChannel->type_id === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                continue;
            }

            $saleChannelWebhookUrl = $saleChannel->saleChannelWebhookUrls
                ->firstWhere('webhook_url_type_id', WebhookUrls::CATEGORY_UNAVAILABLE->value);

            $url = $saleChannelWebhookUrl->url;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'existing_id' => $categoryChannelReference->external_category_id,
            ]);

            if ($response->successful()) {
                Log::channel('e_commerce')->info('Response: success on Category in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => 'Category is unavailable in E-Commerce',
                    'request_data' => [
                        'category_id' => $category->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            } else {
                Log::channel('e_commerce')->info('Response: Error on Category in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'category_id' => $category->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('e-commerce webhook category unavailable details ended', [
            'end time of the webhook call for the category unavailable' => Carbon::now()->format('Y-m-d H:i:s'),
            'category id: ' . $category->getKey(),
        ]);
    }

    private function preparedRecord(Category $category, array $categoryData): array
    {
        $categoryData['status'] = $category->status;
        $categoryData['code'] = $category->code;
        $categoryData['is_display_on_menu'] = $category->is_display_on_menu;
        $categoryData['description'] = $category->description;
        $categoryData['square_image'] = $category->getDiskBasedFirstMediaUrl('square_image');
        $categoryData['landscape_image'] = $category->getDiskBasedFirstMediaUrl('landscape_images');

        return $categoryData;
    }

    private function fetchCategoryRecords(int $categoryId): Category
    {
        $categoryQueries = resolve(CategoryQueries::class);

        return $categoryQueries->getCategoryByIdForEcommerce($categoryId);
    }

    private function fetchCategoryChannelReference(int $categoryId): ?CategoryChannelReference
    {
        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);

        return $categoryChannelReferenceQueries->getCategoryIdForWebspert($categoryId);
    }
}
