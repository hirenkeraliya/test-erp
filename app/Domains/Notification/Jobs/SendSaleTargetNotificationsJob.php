<?php

declare(strict_types=1);

namespace App\Domains\Notification\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\SaleTarget;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSaleTargetNotificationsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public Collection $saleTargets;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $saleTargetIds
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $this->saleTargets = $saleTargetQueries->getByIds($this->saleTargetIds);

        try {
            foreach ($this->saleTargets as $saleTarget) {
                if ($saleTarget->getTargetType() === TargetType::PROMOTER_WISE->value && $saleTarget->promoters->isNotEmpty()) {
                    $this->promoterWiseNotification($saleTarget);
                }

                if ($saleTarget->getTargetType() !== TargetType::STORE_WISE->value) {
                    continue;
                }

                if (! $saleTarget->locations->isNotEmpty()) {
                    continue;
                }

                $this->storeWiseNotification($saleTarget);
            }
        } catch (Throwable $throwable) {
            Log::error('Send Sale Target Notification Job Error', [
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

    private function storeWiseNotification(SaleTarget $saleTarget): void
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        foreach ($saleTarget->saleTargetTimeframes as $saleTargetTimeframe) {
            foreach ($saleTargetTimeframe->saleAchievedTargets->where(
                'targetable_type',
                ModelMapping::LOCATION->name
            )->groupBy(['targetable_id', 'targetable_type'])->collapse() as $saleAchievedTargets) {
                $saleAchievedTarget = $saleAchievedTargets->first();

                if ((float) $saleAchievedTargets->sum(
                    'achieved_value'
                ) >= (float) $saleAchievedTarget->target_value) {
                    $storeManagers = $storeManagerQueries->getAllStoreManagerWithLocations(
                        [$saleAchievedTarget->targetable_id]
                    );
                    foreach ($storeManagers as $storeManager) {
                        foreach ($storeManager->locations->where(
                            'id',
                            $saleAchievedTarget->targetable_id
                        ) as $location) {
                            $message = 'Congratulations! ' . $location->getNameWithCode() . ' You Have Successfully Achieved Your Sales Target.';
                            $textMessage = 'Congratulations! ' . $location->getNameWithCode() . ' You Have Successfully Achieved Your Sales Target.';
                            $payload = [
                                'id' => $saleTarget->id,
                                'location_id' => $location->getKey(),
                                'type' => ModelMapping::SALE_TARGET->name,
                            ];
                            $this->createNotification(
                                $saleTarget->getCompanyId(),
                                $storeManager->getKey(),
                                ModelMapping::getCaseName($storeManager::class),
                                $message,
                                $textMessage,
                                $payload,
                            );
                        }
                    }
                }

                if ((float) $saleAchievedTargets->sum(
                    'achieved_value'
                ) < (float) $saleAchievedTarget->target_value) {
                    $date = Carbon::now()->format('Y-m-d');

                    if ($saleTargetTimeframe->start_date >= $date || $saleTargetTimeframe->end_date >= $date) {
                        return;
                    }

                    $storeManagers = $storeManagerQueries->getAllStoreManagerWithLocations(
                        [$saleAchievedTarget->targetable_id]
                    );
                    foreach ($storeManagers as $storeManager) {
                        foreach ($storeManager->locations->where(
                            'id',
                            $saleAchievedTarget->targetable_id
                        ) as $location) {
                            $message = 'We regret to inform you that the sales target has not been achieved.';
                            $textMessage = 'We regret to inform you that the sales target has not been achieved.';
                            $payload = [
                                'id' => $saleTarget->id,
                                'location_id' => $location->getKey(),
                                'type' => ModelMapping::SALE_TARGET->name,
                            ];
                            $this->createNotification(
                                $saleTarget->getCompanyId(),
                                $storeManager->getKey(),
                                ModelMapping::getCaseName($storeManager::class),
                                $message,
                                $textMessage,
                                $payload,
                            );
                        }
                    }
                }
            }
        }
    }

    private function promoterWiseNotification(SaleTarget $saleTarget): void
    {
        foreach ($saleTarget->saleTargetTimeframes as $saleTargetTimeframe) {
            foreach ($saleTargetTimeframe->saleAchievedTargets->where(
                'targetable_type',
                ModelMapping::PROMOTER->name
            )->groupBy(['targetable_id', 'targetable_type'])->collapse() as $saleAchievedTargets) {
                $saleAchievedTarget = $saleAchievedTargets->first();

                if ((float) $saleAchievedTargets->sum(
                    'achieved_value'
                ) >= (float) $saleAchievedTarget->target_value) {
                    $message = 'Congratulations! You Have Successfully Achieved Your Sales Target.';
                    $textMessage = 'Congratulations! You Have Successfully Achieved Your Sales Target.';

                    $payload = [
                        'id' => $saleTarget->id,
                        'type' => ModelMapping::SALE_TARGET->name,
                    ];
                    $this->createNotification(
                        $saleTarget->getCompanyId(),
                        $saleAchievedTarget->targetable_id,
                        ModelMapping::PROMOTER->name,
                        $message,
                        $textMessage,
                        $payload,
                    );
                }

                if ((float) $saleAchievedTargets->sum(
                    'achieved_value'
                ) < (float) $saleAchievedTarget->target_value) {
                    $date = Carbon::now()->format('Y-m-d');

                    if ($saleTargetTimeframe->start_date >= $date || $saleTargetTimeframe->end_date >= $date) {
                        return;
                    }

                    $message = 'We regret to inform you that the sales target has not been achieved.';
                    $textMessage = 'We regret to inform you that the sales target has not been achieved.';

                    $payload = [
                        'id' => $saleTarget->id,
                        'type' => ModelMapping::SALE_TARGET->name,
                    ];
                    $this->createNotification(
                        $saleTarget->getCompanyId(),
                        $saleAchievedTarget->targetable_id,
                        ModelMapping::PROMOTER->name,
                        $message,
                        $textMessage,
                        $payload,
                    );
                }
            }
        }
    }

    private function createNotification(
        int $companyId,
        int $toUserId,
        string $toUsername,
        string $message,
        string $textMessage,
        array $payload,
    ): void {
        $notificationQueries = resolve(NotificationQueries::class);

        $notificationQueries->addNew(
            $companyId,
            null,
            null,
            $toUsername,
            $toUserId,
            $message,
            null,
            $textMessage,
            $payload
        );
    }
}
