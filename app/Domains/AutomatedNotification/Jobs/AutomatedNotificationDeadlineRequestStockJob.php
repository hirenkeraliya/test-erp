<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomatedNotificationDeadlineRequestStockJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $automatedNotificationId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $notificationQueries = resolve(NotificationQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

        $allStoreManager = $storeManagerQueries->getAllByStoreCompanyId($this->companyId);
        $allWarehouseManager = $warehouseManagerQueries->getAllByWarehouseCompanyId($this->companyId);

        $message = 'Reminder for deadline of request stock';
        $textMessage = 'Reminder for deadline of request stock';

        try {
            foreach ($allStoreManager as $storeManager) {
                $notificationQueries->addNew(
                    $this->companyId,
                    null,
                    null,
                    ModelMapping::STORE_MANAGER->name,
                    $storeManager->id,
                    $message,
                    null,
                    $textMessage,
                    null,
                );
            }

            foreach ($allWarehouseManager as $warehouseManager) {
                $notificationQueries->addNew(
                    $this->companyId,
                    null,
                    null,
                    ModelMapping::WAREHOUSE_MANAGER->name,
                    $warehouseManager->id,
                    $message,
                    null,
                    $textMessage,
                    null,
                );
            }
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Deadline Request Stock Job Error', [
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
