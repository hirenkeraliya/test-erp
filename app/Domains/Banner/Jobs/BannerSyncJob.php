<?php

declare(strict_types=1);

namespace App\Domains\Banner\Jobs;

use App\Domains\Banner\BannerQueries;
use App\Domains\Banner\Services\BannerService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BannerSyncJob implements ShouldQueue
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
        $bannerQueries = resolve(BannerQueries::class);
        $banners = $bannerQueries->getBannerEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);

        $bannerService = resolve(BannerService::class);
        try {
            foreach ($banners as $banner) {
                $bannerService->addUpdateDetails($banner, $saleChannel);
            }
        } catch (Throwable $throwable) {
            Log::error('Banner sync error', [
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
