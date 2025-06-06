<?php

declare(strict_types=1);

namespace App\Domains\Color\Jobs;

use App\Domains\Color\ColorQueries;
use App\Domains\Color\Services\ColorSaleChannelService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\Color;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ColorSyncJob implements ShouldQueue
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
        $colorQueries = resolve(ColorQueries::class);
        $colors = $colorQueries->getColorEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);

        try {
            foreach ($colors as $color) {
                $colorSaleChannelService = resolve(ColorSaleChannelService::class);

                /** @var Color $color */
                $colorSaleChannelService->addColor($saleChannel, $color);
            }
        } catch (Throwable $throwable) {
            Log::error('Color sync error', [
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
