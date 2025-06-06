<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Jobs;

use App\Domains\Admin\AdminQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\Notification\NotificationQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DreamPriceOverlayRestrictionJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('dream_price_product_and_store_duplication')->info('dream_price_product_and_store_duplication', [
            'Dream Price Product And Store Duplication Job Start: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPrices = $dreamPriceQueries->getAllActiveDreamPrice();

        try {
            $this->addNotification($dreamPrices);
        } catch (Throwable $throwable) {
            Log::error('Dream Price Overlay Restriction Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('dream_price_product_and_store_duplication')->info('dream_price_product_and_store_duplication', [
            'Dream Price Product And Store Duplication Job completed: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function addNotification(Collection $duplicationDreamPrices): void
    {
        $duplicationDreamPrices = $duplicationDreamPrices->groupBy('dream_price_company_id_1');

        $adminQueries = resolve(AdminQueries::class);
        $notificationQueries = resolve(NotificationQueries::class);
        foreach ($duplicationDreamPrices as $companyId => $duplicationDreamPrices) {
            foreach ($duplicationDreamPrices as $duplicationDreamPrice) {
                if ('' === $companyId) {
                    return;
                }

                $admins = $adminQueries->getByCompanyIdOnlyId($companyId);
                $message = 'This Are The Dream Price Having The Same Stores and Products, Please Check ' . $duplicationDreamPrice->dream_price_name_1 . ' and ' . $duplicationDreamPrice->dream_price_name_2;
                $textMessage = 'This Are The Dream Price Having The Same Stores and Products, Please Check ' . $duplicationDreamPrice->dream_price_name_1 . ' and ' . $duplicationDreamPrice->dream_price_name_2;

                $payload = [
                    'type' => ModelMapping::DREAM_PRICE->name,
                    'id' => $duplicationDreamPrice->dream_price_id_1,
                    'id_2' => $duplicationDreamPrice->dream_price_id_2,
                ];

                foreach ($admins as $admin) {
                    $notificationQueries->addNew(
                        $companyId,
                        null,
                        null,
                        ModelMapping::ADMIN->name,
                        $admin->id,
                        $message,
                        null,
                        $textMessage,
                        $payload,
                    );
                }
            }
        }
    }
}
