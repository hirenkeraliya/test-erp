<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\Product\ProductQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class PrepareExternalProductsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $externalProductId,
        private readonly int $companyId,
        private readonly User $user,
    ) {
    }

    public function handle(): void
    {
        $productQueries = resolve(ProductQueries::class);
        $externalProductQueries = resolve(ExternalProductQueries::class);
        $externalProduct = $externalProductQueries->getExternalProductByIdAndCompanyId(
            $this->externalProductId,
            $this->companyId
        );

        $product = $productQueries->existsByUpc($externalProduct->upc, $externalProduct->company_id);

        try {
            if ($product) {
                $externalProductQueries->changeStatus($externalProduct, ExternalProductStatuses::DUPLICATE->value);

                return;
            }

            $externalProductQueries->changeStatus($externalProduct, ExternalProductStatuses::IN_PROGRESS->value);

            CreateProductFromExternalProductJob::dispatch(
                $this->externalProductId,
                $this->companyId,
                $this->user
            )->onQueue('medium');
        } catch (Throwable $throwable) {
            Log::error('Prepared External Products Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
