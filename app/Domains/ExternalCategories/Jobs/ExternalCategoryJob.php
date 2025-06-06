<?php

declare(strict_types=1);

namespace App\Domains\ExternalCategories\Jobs;

use App\Domains\ExternalCategories\Services\CategoryWebspertService;
use App\Domains\SaleChannel\SaleChannelQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalCategoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getWebspertSaleChannel();

        if (! $saleChannel) {
            Log::channel('external_categories')->info('sale channel is not available');

            return;
        }

        $categoryWebspertService = resolve(CategoryWebspertService::class);

        try {
            $categoryWebspertService->fetchCategories($saleChannel);
        } catch (Throwable $throwable) {
            Log::error('External Category sync error', [
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
