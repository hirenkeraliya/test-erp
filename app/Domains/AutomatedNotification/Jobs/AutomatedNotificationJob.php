<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Jobs;

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Models\AutomatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomatedNotificationJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->processNotificationType(AutomatedNotificationTypes::LOW_STOCK_COMPANY->value);
            $this->processNotificationType(AutomatedNotificationTypes::LOW_STOCK_LOCATION->value);
            $this->processNotificationType(AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
            $this->processNotificationType(AutomatedNotificationTypes::NO_STOCK->value);
            $this->processNotificationType(AutomatedNotificationTypes::REQUEST_STOCK->value);
            $this->processNotificationType(AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value);
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Job Error', [
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

    public function dispatchNotificationJob(
        int $typeId,
        AutomatedNotification $automatedNotification,
        int $companyId
    ): void {
        if (
            $typeId === AutomatedNotificationTypes::LOW_STOCK_COMPANY->value ||
            $typeId === AutomatedNotificationTypes::LOW_STOCK_LOCATION->value ||
            $typeId === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value &&
            $automatedNotification->sent_notification
        ) {
            AutomatedNotificationLowStockJob::dispatch($automatedNotification->id)->onQueue('medium');
        }

        if ($typeId === AutomatedNotificationTypes::NO_STOCK->value) {
            AutomatedNotificationNoStockJob::dispatch($automatedNotification->id, $companyId)->onQueue('medium');
        }

        if ($typeId === AutomatedNotificationTypes::REQUEST_STOCK->value) {
            AutomatedNotificationRequestStockJob::dispatch($automatedNotification->id, $companyId)->onQueue('medium');
        }

        if ($typeId === AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value) {
            AutomatedNotificationDeadlineRequestStockJob::dispatch($automatedNotification->id, $companyId)->onQueue(
                'medium'
            );
        }
    }

    private function processNotificationType(int $typeId): void
    {
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);

        $allNotifications = $automatedNotificationQueries->getByTypeIdWithRelations($typeId);

        $currentDay = now()->day;
        $currentWeekDay = now()->dayOfWeek;

        foreach ($allNotifications as $allNotification) {
            $monthly = $allNotification->monthly;
            $weekly = $allNotification->weekly;

            $monthlyStatus = $this->checkMonthlyNotifications($monthly, $currentDay);
            $weeklyStatus = $this->checkWeeklyNotifications($weekly, $currentWeekDay);

            $status = $monthlyStatus || $weeklyStatus;
            if ($status) {
                $this->dispatchNotificationJob($typeId, $allNotification, $allNotification->company_id);
            }
        }
    }

    private function checkMonthlyNotifications(Collection $monthly, int $currentDay): bool
    {
        foreach ($monthly as $month) {
            if ($currentDay === $month->month_date) {
                return true;
            }
        }

        return false;
    }

    private function checkWeeklyNotifications(Collection $weekly, int $currentWeekDay): bool
    {
        foreach ($weekly as $week) {
            if ($currentWeekDay === $week->week_day) {
                return true;
            }
        }

        return false;
    }
}
