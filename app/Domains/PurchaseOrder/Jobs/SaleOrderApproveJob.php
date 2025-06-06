<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Jobs;

use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SaleOrderApproveJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $purchaseOrderId,
        private readonly int $companyId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('sale_order_approve_job')->info('sale_order_approve_job', [
            'The job for sale order approve has started. Sale Order Id: ' . $this->purchaseOrderId,
        ]);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation(
            $this->purchaseOrderId,
            $this->companyId
        );

        if ($purchaseOrder->status !== Statuses::OPENED->value) {
            return;
        }

        if (null !== $purchaseOrder->created_by_company_id) {
            return;
        }

        DB::beginTransaction();

        try {
            $purchaseOrderService = resolve(PurchaseOrderService::class);
            $purchaseOrderService->purchaseOrderApprove($purchaseOrder, null);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            $this->fail($throwable);
        }

        Log::channel('sale_order_approve_job')->info('sale_order_approve_job', [
            'The sale order approve has job has finished. Date: ' . $this->purchaseOrderId,
        ]);
    }
}
