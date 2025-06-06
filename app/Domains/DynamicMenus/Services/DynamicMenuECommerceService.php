<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenus\Services;

use App\Domains\BrandChannelReference\BrandChannelReferenceQueries;
use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\DynamicMenuChannelReference\DynamicMenuChannelReferenceQueries;
use App\Domains\DynamicMenus\Enums\DynamicMenuTypesEnum;
use App\Domains\ProductCollectionChannelReference\ProductCollectionChannelReferenceQueries;
use App\Models\DynamicMenu;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DynamicMenuECommerceService
{
    public function createOrUpdateDynamicMenu(DynamicMenu $dynamicMenu, SaleChannel $saleChannel): void
    {
        Log::channel('e_commerce')->info('Start creating or updating the Dynamic Menu in eCommerce.', [
            'Start time for Dynamic Menu creation or update' => Carbon::now()->format('Y-m-d H:i:s'),
            'Dynamic Menu id: ' . $dynamicMenu->getKey(),
        ]);

        $dynamicMenuChannelReferenceQueries = resolve(DynamicMenuChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            $dynamicMenuChannelReference = $dynamicMenuChannelReferenceQueries->getByMenuIdAndSaleChannelId(
                $dynamicMenu->id,
                $saleChannel->id
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $saleChannel->secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post($url, [
                'dynamic_menu' => $this->preparedRecords($dynamicMenu, $saleChannel->id),
            ]);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                Log::channel('e_commerce')->info('Response: Dynamic Menu in E-Commerce', [
                    'response' => $responseData,
                ]);

                if (array_key_exists('menu_id', $responseData) && ! $dynamicMenuChannelReference) {
                    $dynamicMenuChannelReferenceQueries = resolve(DynamicMenuChannelReferenceQueries::class);
                    $dynamicMenuChannelReferenceQueries->addNew([
                        'sale_channel_id' => $saleChannel->id,
                        'dynamic_menu_id' => $dynamicMenu->id,
                        'external_menu_id' => $responseData['menu_id'],
                    ]);
                }
            } else {
                Log::channel('e_commerce')->info('Response: Error on Dynamic Menu in E-Commerce', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body() ?: 'No response body provided',
                    'request_data' => [
                        'menu_id' => $dynamicMenu->getKey(),
                        'saleChannel_id' => $saleChannel->getKey(),
                    ],
                ]);
            }
        }

        Log::channel('e_commerce')->info('End creating or updating the Dynamic Menu in eCommerce.', [
            'End time for Dynamic Menu creation or update' => Carbon::now()->format('Y-m-d H:i:s'),
            'Dynamic Menu id: ' . $dynamicMenu->getKey(),
        ]);
    }

    private function preparedRecords(DynamicMenu $dynamicMenu, int $saleChannelId): array
    {
        $dynamicMenuChannelReferenceForParent = null;
        $dynamicMenuChannelReferenceQueries = resolve(DynamicMenuChannelReferenceQueries::class);
        $dynamicMenuChannelReference = $dynamicMenuChannelReferenceQueries->getByMenuIdAndSaleChannelId(
            $dynamicMenu->id,
            $saleChannelId
        );

        if ($dynamicMenu->parent_id) {
            $dynamicMenuChannelReferenceForParent = $dynamicMenuChannelReferenceQueries->getByMenuIdAndSaleChannelId(
                $dynamicMenu->parent_id,
                $saleChannelId
            );
        }

        $externalId = $dynamicMenuChannelReference?->external_menu_id;
        $moduleId = $this->resolveModuleId($dynamicMenu, $saleChannelId);

        return [
            'external_id' => $externalId,
            'title' => $dynamicMenu->title,
            'slug' => $dynamicMenu->slug,
            'parent_id' => $dynamicMenuChannelReferenceForParent?->external_menu_id,
            'type' => $dynamicMenu->type,
            'module_id' => $moduleId,
            'content' => $dynamicMenu->content,
            'status' => $dynamicMenu->status,
        ];
    }

    private function resolveModuleId(DynamicMenu $dynamicMenu, int $saleChannelId): ?int
    {
        return match ((int) $dynamicMenu->type) {
            DynamicMenuTypesEnum::BRAND->value => $this->getBrandModuleId(
                (int) $dynamicMenu->module_id,
                $saleChannelId
            ),
            DynamicMenuTypesEnum::CATEGORIES->value => $this->getCategoryModuleId((int) $dynamicMenu->module_id),
            DynamicMenuTypesEnum::PRODUCT_COLLECTION->value => $this->getProductCollectionModuleId(
                (int) $dynamicMenu->module_id
            ),
            default => null,
        };
    }

    private function getBrandModuleId(int $moduleId, int $saleChannelId): int
    {
        $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);
        $brands = $brandChannelReferenceQueries->getByBrandIdAndSaleChannelId($moduleId, $saleChannelId);

        return (int) $brands?->external_brand_id;
    }

    private function getCategoryModuleId(int $moduleId): int
    {
        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);
        $categories = $categoryChannelReferenceQueries->getExternalCategoryIdFromCategoryId($moduleId);

        return (int) $categories?->external_category_id;
    }

    private function getProductCollectionModuleId(int $moduleId): int
    {
        $productCollectionChannelReferenceQueries = resolve(ProductCollectionChannelReferenceQueries::class);
        $productCollection = $productCollectionChannelReferenceQueries->getProductCollectionIdIdForEcommerce($moduleId);

        return (int) $productCollection?->external_product_collection_id;
    }
}
