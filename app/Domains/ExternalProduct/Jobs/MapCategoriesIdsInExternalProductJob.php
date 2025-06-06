<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\ExternalProduct\Services\ExternalProductServices;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class MapCategoriesIdsInExternalProductJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        /** @var SaleChannel $saleChannel */
        $saleChannel = $saleChannelQueries->getWebspertSaleChannel();

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $externalProducts = $productChannelReferenceQueries->getProductsByChannelId($saleChannel->id);

        try {
            $externalProductServices = resolve(ExternalProductServices::class);
            $externalProductServices->fetchExternalProduct($saleChannel, $externalProducts);
        } catch (Throwable $throwable) {
            Log::error('Create Product From External Product Job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('product_channel_reference_categories')->info('product_channel_reference_categories', [
            'Create product from external product job end time is: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
