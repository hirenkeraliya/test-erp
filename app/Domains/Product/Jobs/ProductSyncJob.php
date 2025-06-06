<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductEcommerceService;
use App\Domains\Product\Services\ProductWebspertService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $startId,
        private readonly int $endId,
        private readonly int $saleChannelId,
        private readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getProductEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        if ($products->isEmpty()) {
            return;
        }

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);

        $productEcommerceService = resolve(ProductEcommerceService::class);
        $productWebspertService = resolve(ProductWebspertService::class);

        try {
            foreach ($products as $product) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    $productEcommerceService->addProduct($saleChannel, $product);
                }

                if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                    $productWebspertService->createProductOnWebspert($product);
                }
            }
        } catch (Throwable $throwable) {
            Log::error('Product sync error', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
