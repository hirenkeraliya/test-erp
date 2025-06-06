<?php

declare(strict_types=1);

namespace App\Domains\Brand\Jobs;

use App\Domains\Brand\BrandQueries;
use App\Domains\Brand\Services\BrandSaleChannelService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BrandSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $startId,
        private readonly int $endId,
        private readonly int $saleChannelId,
    ) {
    }

    public function handle(): void
    {
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrandEcommerceChannelByStartAndEndId(
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);
        $brandSaleChannelService = resolve(BrandSaleChannelService::class);

        try {
            foreach ($brands as $brand) {
                /** @var Brand $brand */
                $brandSaleChannelService->addBrand($saleChannel, $brand);
            }
        } catch (Throwable $throwable) {
            Log::error('Brand sync error', [
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
