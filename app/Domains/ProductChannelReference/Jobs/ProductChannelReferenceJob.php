<?php

declare(strict_types=1);

namespace App\Domains\ProductChannelReference\Jobs;

use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Services\WebspertIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductChannelReferenceJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $webspertIntegrationService = resolve(WebspertIntegrationService::class);

        $saleChannels = $saleChannelQueries->getSpecificTypeOfSaleChannelWithWebHooks(
            SaleChannelTypes::WEBSPERT_ECOMMERCE
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('product_channel_reference')->info('sale channel is not available');

            return;
        }

        DB::beginTransaction();
        try {
            $staticPath = '/product/get_product_list';
            foreach ($saleChannels as $saleChannel) {
                $products = $webspertIntegrationService->getEcommerceProducts($saleChannel, $staticPath);
                if (! empty($products)) {
                    foreach ($products['data']['items'] as $product) {
                        if (! array_key_exists('item_barcode', $product)) {
                            continue;
                        }

                        $firstProduct = $productQueries->getUpcAndIsAvailableInEcommerceByUpc($product['item_barcode']);

                        if (null === $firstProduct) {
                            return;
                        }

                        $productChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->getKey(),
                            'product_id' => $firstProduct->getKey(),
                            'external_product_id' => $product['item_id'],
                            'external_variant_id' => null,
                        ]);

                        if (count($product['variations']) > 0) {
                            foreach ($product['variations'] as $variation) {
                                $upc = $variation['variation_barcode'] ?: $variation['variation_sku'];

                                $variantProduct = $productQueries->getByUpcAndCompanyId(
                                    $upc,
                                    $saleChannel->getCompanyId()
                                );

                                if ($variantProduct) {
                                    $productChannelReferenceQueries->addNew([
                                        'sale_channel_id' => $saleChannel->getKey(),
                                        'product_id' => $variantProduct->getKey(),
                                        'external_product_id' => $product['item_id'],
                                        'external_variant_id' => $variation['variation_id'],
                                    ]);
                                }
                            }
                        }

                        $productQueries->updateIsAvailableInEcommerce($firstProduct);
                    }
                }
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'Product Channel Reference Job job error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);

            $this->fail($throwable);
        }
    }
}
