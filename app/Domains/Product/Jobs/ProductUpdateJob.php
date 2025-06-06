<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Product $product,
        protected bool $oldStatus
    ) {
    }

    public function handle(): void
    {
        if (! $this->oldStatus) {
            return;
        }

        $product = $this->product;

        $product->refresh();

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->loadCategoriesForProduct($product);

        /** @var Collection $categories */
        $categories = $product->categories;

        /** @var Carbon $updatedAt */
        $updatedAt = $product->updated_at;

        $saleChannelQueries = resolve(SaleChannelQueries::class);

        $webhookUrls = [WebhookUrls::PRODUCT_DELETE->value];

        $saleChannels = $saleChannelQueries->getSaleChannelsByCompany($webhookUrls, $product->company_id);

        if ($saleChannels->isEmpty()) {
            return;
        }

        Log::channel('e_commerce')->info('e-commerce webhook product delete started', [
            'start time of the webhook call for the product delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);

        try {
            foreach ($saleChannels as $saleChannel) {
                if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                    continue;
                }

                $saleChannelWebhookUrls = $saleChannel->saleChannelWebhookUrls;

                foreach ($saleChannelWebhookUrls as $saleChannelWebhookUrl) {
                    Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->timeout(config('services.http_time_out'))->post($saleChannelWebhookUrl->url, [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'code' => $product->code,
                        'season' => $product->season_id,
                        'department' => $product->department_id,
                        'color' => $product->color_id,
                        'size' => $product->size_id,
                        'brand' => $product->brand_id,
                        'style' => $product->style_id,
                        'upc' => $product->upc,
                        'ean' => $product->ean,
                        'custom_sku' => $product->custom_sku,
                        'manufacturer_sku' => $product->manufacturer_sku,
                        'article_number' => $product->article_number,
                        'price' => (float) $product->retail_price,
                        'online_price' => (float) $product->online_price,
                        'status' => Statuses::getFormattedCaseName(Statuses::INACTIVE->value),
                        'categories' => $categories->map(function ($category): array {
                            /** @var Category $productCategory */
                            $productCategory = $category;

                            $productCategoryPivot = $category->pivot;

                            return [
                                'id' => $productCategory->id,
                                'sort_order' => $productCategoryPivot->sort_order,
                            ];
                        }),
                        'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                        'thumbnail' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
                        'images' => $product->getDiskBasedMediaUrls('images'),
                        'videos' => $product->getDiskBasedMediaUrls('videos'),
                    ]);
                }
            }
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook product delete failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook product delete ended', [
            'end time of the webhook call for the product delete' => Carbon::now()->format('Y-m-d H:i:s'),
            'product id: ' . $product->getKey(),
        ]);
    }
}
