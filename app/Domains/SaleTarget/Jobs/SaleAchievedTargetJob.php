<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Jobs;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\Jobs\SendSaleTargetNotificationsJob;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\SaleAchievedTarget;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SaleAchievedTargetJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly ?int $saleTargetId = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('sale_achieved_target_job')->info('sale-achieved-target-job', [
            'The job for sale achieved target has started. Date: ' . now()->format('Y-m-d'),
        ]);

        $saleTargetTimeframes = $this->getSaleTargetTimeframes($this->saleTargetId);

        $saleTargetIds = collect();

        foreach ($saleTargetTimeframes as $saleTargetTimeframe) {
            $saleTargetIds->push($saleTargetTimeframe->sale_target_id);
            $this->companyWiseTargetStore($saleTargetTimeframe);
            $this->storeWiseTargetStore($saleTargetTimeframe);
            $this->promoterWiseTargetStore($saleTargetTimeframe);
        }

        try {
            foreach ($saleTargetIds->filter()->unique()->chunk(30) as $saleTargetIds) {
                SendSaleTargetNotificationsJob::dispatch($saleTargetIds->toArray())->onQueue('medium');
            }

            $this->markAsRegenerateCompete($this->saleTargetId);
        } catch (Throwable $throwable) {
            Log::error('Sale Achieved Target Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('sale_achieved_target_job')->info('sale-achieved-target-job', [
            'The job for sale achieved target has end. Date: ' . now()->format('Y-m-d'),
        ]);
    }

    public function getSaleTargetTimeframes(?int $saleTargetId): Collection
    {
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        if ($saleTargetId) {
            return $saleTargetTimeframeQueries->getBySaleTargetId($saleTargetId);
        }

        return $saleTargetTimeframeQueries->getByStartAndEndDate();
    }

    public function markAsRegenerateCompete(?int $saleTargetId): void
    {
        if (! $saleTargetId) {
            return;
        }

        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $saleTargetQueries->markAsRegenerateCompete($saleTargetId);
    }

    public function companyWiseTargetStore(SaleTargetTimeframe $saleTargetTimeframe): void
    {
        $saleTarget = $saleTargetTimeframe->saleTarget;

        if (! $saleTarget) {
            return;
        }

        if ($saleTarget->target_type !== TargetType::COMPANY_WISE->value) {
            return;
        }

        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        $sale = $saleQueries->getTotalAmountForSaleCompanyTarget(
            $saleTargetTimeframe->start_date,
            $saleTargetTimeframe->end_date,
            $saleTarget->company_id
        );

        $saleReturn = $saleReturnQueries->getTotalAmountForSaleCompanyTarget(
            $saleTargetTimeframe->start_date,
            $saleTargetTimeframe->end_date,
            $saleTarget->company_id
        );

        $saleAchievedTarget = $saleTargetTimeframe->saleAchievedTargets->first();

        $achievedValue = (float) $sale['total_sales_amount'] - (float) $saleReturn['total_return_amount'];

        if ($saleAchievedTarget) {
            $this->updateSaleAchievedTarget($saleAchievedTarget, $saleTarget, $achievedValue, $saleTargetTimeframe);

            return;
        }

        $this->addSaleAchievedTarget($saleTarget, $saleTargetTimeframe, $achievedValue, $saleTarget->company_id);
    }

    public function updateSaleAchievedTarget(
        SaleAchievedTarget $saleAchievedTarget,
        SaleTarget $saleTarget,
        float $achievedValue,
        SaleTargetTimeframe $saleTargetTimeframe,
    ): void {
        $saleAchievedTargetRecord = [
            'achieved_value' => CommonFunctions::numberFormat($achievedValue),
        ];

        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);
        $saleAchievedTargetQueries->updateAchievedValue($saleAchievedTarget, $saleAchievedTargetRecord);
    }

    public function addSaleAchievedTarget(
        SaleTarget $saleTarget,
        SaleTargetTimeframe $saleTargetTimeframe,
        float $achievedValue,
        int $targetableId
    ): void {
        $targetableType = $this->getTargetableType($saleTarget);

        $amount = $saleTarget->amount;

        if ($saleTarget->time_interval_type === TimeIntervalType::WEEKLY->value || $saleTarget->time_interval_type === TimeIntervalType::MONTHLY->value) {
            $amount = $saleTargetTimeframe->amount;
        }

        $saleAchievedTargetRecord = [
            'sale_target_timeframe_id' => $saleTargetTimeframe->id,
            'targetable_id' => $targetableId,
            'targetable_type' => $targetableType,
            'target_value' => $amount,
            'achieved_value' => CommonFunctions::numberFormat($achievedValue),
        ];

        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);
        $saleAchievedTargetQueries->addNew($saleAchievedTargetRecord);
    }

    public function getTargetableType(SaleTarget $saleTarget): string
    {
        if ($saleTarget->target_type === TargetType::PROMOTER_WISE->value) {
            return ModelMapping::PROMOTER->name;
        }

        if ($saleTarget->target_type === TargetType::STORE_WISE->value) {
            return ModelMapping::LOCATION->name;
        }

        return ModelMapping::COMPANY->name;
    }

    public function storeWiseTargetStore(SaleTargetTimeframe $saleTargetTimeframe): void
    {
        $saleTarget = $saleTargetTimeframe->saleTarget;

        if (! $saleTarget) {
            return;
        }

        if ($saleTarget->target_type !== TargetType::STORE_WISE->value) {
            return;
        }

        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        $locationIds = $saleTarget->locations->pluck('id')->toArray();

        $sales = $saleQueries->getTotalAmountForSaleStoreTarget(
            $saleTargetTimeframe->start_date,
            $saleTargetTimeframe->end_date,
            $locationIds
        );

        $saleReturns = $saleReturnQueries->getTotalAmountForSaleStoreTarget(
            $saleTargetTimeframe->start_date,
            $saleTargetTimeframe->end_date,
            $locationIds
        );

        $concatSales = $sales->concat($saleReturns);
        $this->storeWiseTargetStoreForSales($saleTargetTimeframe, $concatSales, $locationIds);
    }

    public function storeWiseTargetStoreForSales(
        SaleTargetTimeframe $saleTargetTimeframe,
        Collection $sales,
        array $locationIds
    ): void {
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        $saleTarget = $saleTargetTimeframe->saleTarget;

        if (! $saleTarget) {
            return;
        }

        if ($sales->count() === 0) {
            foreach ($locationIds as $locationId) {
                $this->addSaleAchievedTarget($saleTarget, $saleTargetTimeframe, (float) 0, $locationId);
            }

            return;
        }

        foreach ($sales as $sale) {
            $sale = $sale->toArray();

            $achievedValue = 0;
            $saleTargetTimeframe = $saleTargetTimeframeQueries->refresh($saleTargetTimeframe);

            $saleAchievedTarget = $saleTargetTimeframe->saleAchievedTargets
                ->where('targetable_id', $sale['location_id'])
                ->firstWhere('targetable_type', ModelMapping::LOCATION->name);

            if (array_key_exists('total_sales_amount', $sale)) {
                $achievedValue += ($sale['total_sales_amount'] + (float) $saleAchievedTarget?->achieved_value);
            }

            if (array_key_exists('total_return_amount', $sale)) {
                $achievedValue += ((float) $saleAchievedTarget?->achieved_value - $sale['total_return_amount']);
            }

            if ($saleAchievedTarget) {
                $this->updateSaleAchievedTarget(
                    $saleAchievedTarget,
                    $saleTarget,
                    (float) $achievedValue,
                    $saleTargetTimeframe
                );

                continue;
            }

            $this->addSaleAchievedTarget(
                $saleTarget,
                $saleTargetTimeframe,
                (float) $achievedValue,
                $sale['location_id']
            );
        }
    }

    public function promoterWiseTargetStore(SaleTargetTimeframe $saleTargetTimeframe): void
    {
        $saleTarget = $saleTargetTimeframe->saleTarget;

        if (! $saleTarget) {
            return;
        }

        if ($saleTarget->target_type !== TargetType::PROMOTER_WISE->value) {
            return;
        }

        $promoterQueries = resolve(PromoterQueries::class);

        $promoterIds = $saleTarget->promoters->pluck('id')->toArray();

        $promoters = $promoterQueries->getTotalAmountForSalePromoterTarget(
            $saleTargetTimeframe->start_date,
            $saleTargetTimeframe->end_date,
            $promoterIds
        );

        if ($promoters->count() === 0) {
            foreach ($promoterIds as $promoterId) {
                $this->addSaleAchievedTarget($saleTarget, $saleTargetTimeframe, 0, $promoterId);
            }

            return;
        }

        foreach ($promoters as $promoter) {
            $saleAchievedTarget = $saleTargetTimeframe->saleAchievedTargets
                ->where('targetable_id', $promoter->id)
                ->firstWhere('targetable_type', ModelMapping::PROMOTER->name);

            $achievedValue = (float) $promoter->amount_sold;

            if ($saleAchievedTarget) {
                $this->updateSaleAchievedTarget($saleAchievedTarget, $saleTarget, $achievedValue, $saleTargetTimeframe);

                continue;
            }

            $this->addSaleAchievedTarget($saleTarget, $saleTargetTimeframe, $achievedValue, $promoter->id);
        }
    }
}
