<?php

declare(strict_types=1);

namespace App\Domains\ProductChannelReference\Jobs;

use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Services\WebspertIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckProductChannelReferenceJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $saleChannelId,
        private readonly int $startId,
        private readonly int $endId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $webspertIntegrationService = resolve(WebspertIntegrationService::class);

        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);
        $companyId = $saleChannel->getCompanyId();

        $products = $productQueries->getProductEcommerceChannelByStartAndEndId(
            $companyId,
            $this->startId,
            $this->endId,
            $saleChannel->id
        );

        try {
            $staticPath = '/product/search_product_detail';

            foreach ($products as $product) {
                $responseData = $webspertIntegrationService->getEcommerceProductDetails(
                    $saleChannel,
                    $staticPath,
                    $product->upc
                );

                if (! array_key_exists('data', $responseData)) {
                    continue;
                }

                if (! array_key_exists('products', $responseData['data'])) {
                    continue;
                }

                foreach ($responseData['data']['products'] as $salesChannelProduct) {
                    if (! array_key_exists('item_id', $salesChannelProduct)) {
                        continue;
                    }

                    if (
                        $salesChannelProduct['item_barcode'] &&
                        $product->upc == $salesChannelProduct['item_barcode'] &&
                        count($salesChannelProduct['variations']) <= 0
                    ) {
                        $productChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->getKey(),
                            'product_id' => $product->getKey(),
                            'external_product_id' => $salesChannelProduct['item_id'],
                            'external_variant_id' => null,
                        ]);
                    } else {
                        $withoutItemBarcodeProduct = $productQueries->getByUpcAndCompanyId(
                            $product->upc,
                            $companyId
                        );

                        if ($withoutItemBarcodeProduct) {
                            $productChannelReferenceQueries->addNew([
                                'sale_channel_id' => $saleChannel->getKey(),
                                'product_id' => $withoutItemBarcodeProduct->getKey(),
                                'external_product_id' => $salesChannelProduct['item_id'],
                                'external_variant_id' => null,
                            ]);
                        }
                    }

                    if (count($salesChannelProduct['variations']) > 0) {
                        foreach ($salesChannelProduct['variations'] as $variation) {
                            $upc = $variation['variation_barcode'] ?: $variation['variation_sku'];

                            $variantProduct = $productQueries->getByUpcAndCompanyId($upc, $companyId);

                            if ($variantProduct) {
                                $productChannelReferenceQueries->addNew([
                                    'sale_channel_id' => $saleChannel->getKey(),
                                    'product_id' => $variantProduct->getKey(),
                                    'external_product_id' => $salesChannelProduct['item_id'],
                                    'external_variant_id' => $variation['variation_id'],
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::error([
                'error_name' => 'Check Product Channel Reference Job job error:',
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
