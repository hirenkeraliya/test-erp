<?php

declare(strict_types=1);

namespace App\Domains\ProductChannelReference\Jobs;

use App\Domains\Product\ProductQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckProductChannelReferenceMainJob implements ShouldQueueAfterCommit
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
        $productQueries = resolve(ProductQueries::class);

        $saleChannels = $saleChannelQueries->getSpecificTypeOfSaleChannelWithWebHooks(
            SaleChannelTypes::WEBSPERT_ECOMMERCE
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('product_channel_reference')->info('Sale Channel is not available');

            return;
        }

        foreach ($saleChannels as $saleChannel) {
            $companyId = $saleChannel->getCompanyId();
            $product = $productQueries->getLastProductIdEcommerceChannel($companyId);

            for ($startId = 0; $startId <= (int) $product['max_id']; $startId += 100) {
                $endId = $startId + 99;

                $productCount = $productQueries->getCountOfProductEcommerceChannelByStartAndEndId(
                    $companyId,
                    $startId,
                    $endId
                );
                if ($productCount <= 0) {
                    continue;
                }

                CheckProductChannelReferenceJob::dispatch($saleChannel->id, $startId, $endId)->onQueue('medium');
            }
        }
    }
}
