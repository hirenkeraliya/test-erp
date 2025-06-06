<?php

declare(strict_types=1);

namespace App\Domains\Notification\Jobs;

use App\Domains\Notification\NotificationQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteOldNotificationsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Log::channel('notifications')->info('notifications', [
            'Start time for Delete Old Notification job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $dateToDelete = Carbon::now()->subDays(60)->format('Y-m-d');

            $notificationQueries = resolve(NotificationQueries::class);

            $notificationQueries->deleteNotifications($dateToDelete);

            Log::channel('notifications')->info('notifications', ['Delete Old Notifications finished.']);
        } catch (Throwable $throwable) {
            Log::channel('notifications')->error('notifications', [
                'Error: Delete Old Notifications job: ' . $throwable->getMessage(),
            ]);

            $this->fail($throwable);
        }

        Log::channel('notifications')->info('notifications', [
            'End time for Delete Old Notifications job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
