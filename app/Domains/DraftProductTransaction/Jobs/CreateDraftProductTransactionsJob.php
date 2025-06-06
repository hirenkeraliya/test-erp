<?php

declare(strict_types=1);

namespace App\Domains\DraftProductTransaction\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DraftProductTransaction\DraftProductTransactionQueries;
use App\Domains\ExternalProduct\Jobs\ExternalCompanyWiseProductJob;
use App\Domains\Product\Enums\Statuses;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateDraftProductTransactionsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $productId,
        private readonly int $companyId,
        private readonly int $userId,
        private readonly string $userType,
        private readonly int $status,
    ) {
    }

    public function handle(): void
    {
        Log::channel('create_draft_product_transactions_job')->info('create_draft_product_transactions_job', [
            'Create draft product transaction job start time is: ' . Carbon::now()->format(
                'Y-m-d H:i:s'
            ) . 'and product id is : ' . $this->productId . ' and company is: ' . $this->companyId,
        ]);

        DB::beginTransaction();
        try {
            $draftProductTransactionData = [];
            $draftProductTransactionData['product_id'] = $this->productId;
            $draftProductTransactionData['rejected_at'] = null;
            $draftProductTransactionData['rejected_by_id'] = null;
            $draftProductTransactionData['rejected_by_type'] = null;

            if ($this->status === Statuses::ACTIVE->value) {
                $draftProductTransactionData['approved_by_id'] = $this->userId;
                $draftProductTransactionData['approved_by_type'] = ModelMapping::getCaseName($this->userType);
                $draftProductTransactionData['approved_at'] = Carbon::now()->format('Y-m-d H:i:s');
            }

            $draftProductTransactionQueries = resolve(DraftProductTransactionQueries::class);
            $draftProductTransactionQueries->addNew($draftProductTransactionData);

            if ($this->status === Statuses::ACTIVE->value) {
                ExternalCompanyWiseProductJob::dispatch($this->productId)->onQueue(
                    config('horizon.default_queue_name')
                );
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Create draft product transaction job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('create_draft_product_transactions_job')->info('create_draft_product_transactions_job', [
            'Create draft product transaction job finish time is: ' . Carbon::now()->format(
                'Y-m-d H:i:s'
            ) . 'and product id is : ' . $this->productId . ' and company is: ' . $this->companyId,
        ]);
    }
}
