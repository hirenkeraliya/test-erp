<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Jobs;

use App\Domains\DreamPrice\Services\DreamPriceEcommerceService;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Models\DreamPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DreamPriceSyncJob implements ShouldQueue
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
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $dreamPriceProducts = $dreamPriceProductQueries->getDreamPriceProductEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $dreamPriceService = resolve(DreamPriceEcommerceService::class);

        try {
            foreach ($dreamPriceProducts as $dreamPriceProduct) {
                /** @var DreamPrice $dreamPrice */
                $dreamPrice = $dreamPriceProduct->dreamPrice;

                $dreamPriceService->updateProductDreamPrice(
                    $dreamPrice->start_date,
                    $dreamPrice->end_date,
                    $this->saleChannelId,
                    $dreamPriceProduct
                );
            }
        } catch (Throwable $throwable) {
            Log::error('Category sync error', [
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
