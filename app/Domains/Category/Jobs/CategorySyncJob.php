<?php

declare(strict_types=1);

namespace App\Domains\Category\Jobs;

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\Services\CategoryEcommerceService;
use App\Domains\Category\Services\CategoryWebspertService;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategorySyncJob implements ShouldQueue
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
        $categoryQueries = resolve(CategoryQueries::class);
        $categories = $categoryQueries->getCategoryEcommerceChannelByStartAndEndId(
            $this->companyId,
            $this->startId,
            $this->endId,
            $this->saleChannelId
        );

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannel = $saleChannelQueries->getByIdAndStatus($this->saleChannelId);

        $categoryEcommerceService = resolve(CategoryEcommerceService::class);
        $categoryWebspertService = resolve(CategoryWebspertService::class);

        try {
            foreach ($categories as $category) {
                if ($saleChannel->getType()->value === SaleChannelTypes::ECOMMERCE->value) {
                    $categoryEcommerceService->createCategory($category, $saleChannel->id);
                }

                if ($saleChannel->getType()->value === SaleChannelTypes::WEBSPERT_ECOMMERCE->value) {
                    $categoryWebspertService->createCategory($category, $saleChannel->id);
                }
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
