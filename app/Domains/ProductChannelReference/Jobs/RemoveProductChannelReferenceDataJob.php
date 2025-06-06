<?php

declare(strict_types=1);

namespace App\Domains\ProductChannelReference\Jobs;

use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RemoveProductChannelReferenceDataJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected array $productIds,
        protected int $saleChannelId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var ProductChannelReferenceQueries $productChannelReferenceQueries */
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);

        $productChannelReferenceQueries->removeReferencesBasedOnSaleChannelAndProductIds(
            $this->productIds,
            $this->saleChannelId
        );
    }
}
