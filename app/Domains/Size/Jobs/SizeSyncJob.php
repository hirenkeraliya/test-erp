<?php

declare(strict_types=1);

namespace App\Domains\Size\Jobs;

use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Size\Services\SizeSaleChannelService;
use App\Domains\Size\SizeQueries;
use App\Models\Size;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SizeSyncJob implements ShouldQueue
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
        $sizeQueries = resolve(SizeQueries::class);
        $sizes = $sizeQueries->getSizeEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);

        try {
            foreach ($sizes as $size) {
                $sizeSaleChannelService = resolve(SizeSaleChannelService::class);
                /** @var Size $size */
                $sizeSaleChannelService->addSize($saleChannel, $size);
            }
        } catch (Throwable $throwable) {
            Log::error('Size sync error', [
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
