<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Jobs;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Company\RoundOffConfigurationToSixDigits;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Promoter;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PromoterCommissionGenerationJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly Carbon $commissionGenerationDate;

    public function __construct(
        public array $promoterIds,
        ?string $commissionGenerationDate = null
    ) {
        /** @var Carbon $commissionGenerationDate */
        $commissionGenerationDate = $commissionGenerationDate
            ? Carbon::createFromFormat('Y-m-d H:i:s', $commissionGenerationDate)
            : now()->subMonthNoOverflow();

        $this->commissionGenerationDate = $commissionGenerationDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('promoter_commission_generation')->info('promoter_commission_generation', [
            'Promoter commission generation job started. Date: ' . $this->commissionGenerationDate . ' Promoter Ids: ' . implode(
                ',',
                $this->promoterIds
            ),
        ]);

        try {
            /** @var Carbon $commissionGenerationDate */
            $commissionGenerationDate = $this->commissionGenerationDate;

            $firstDayOfPreviousMonth = $commissionGenerationDate->startOfMonth()->format('Y-m-d');

            $lastDayOfPreviousMonth = $commissionGenerationDate->endOfMonth()->format('Y-m-d');

            $promoterCommissionQueries = resolve(PromoterCommissionQueries::class);
            $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);

            $promoterQueries = resolve(PromoterQueries::class);
            $promoters = $promoterQueries->getAllWithMonthlySalesAndCompanyDetailsForPeriod(
                $firstDayOfPreviousMonth,
                $lastDayOfPreviousMonth,
                $this->promoterIds
            );

            DB::beginTransaction();
            foreach ($promoters as $promoter) {
                $monthlySaleItems = $promoter->saleItems;

                $monthlySalesAmount = CommonFunctions::numberFormat(
                    (float) $monthlySaleItems->sum('total_price_paid')
                );

                $promoterCommission = $promoterCommissionQueries->addNew([
                    'promoter_id' => $promoter->id,
                    'commission_amount' => 0,
                    'total_sales_amount' => 0,
                    'monthly_sales_target' => $promoter->monthly_sales_target ?? 0,
                    'commission_date' => $firstDayOfPreviousMonth,
                ]);

                [$totalCommissionAmount, $totalAmount] = $this->saveSaleItemsCommission(
                    $promoter,
                    $monthlySaleItems,
                    $monthlySalesAmount,
                    $promoterCommission->id
                );

                /* Returns */
                $returnedItems = $promoterQueries->getPromoterCommissionReturnItemsByIdAndPeriod(
                    $promoter->id,
                    $firstDayOfPreviousMonth,
                    $lastDayOfPreviousMonth
                );

                $totalReturnAmount = 0;

                foreach ($returnedItems as $returnedItem) {
                    $promoterCommissionUpdate = $returnedItem->saleItem->promoterCommissionUpdate;
                    if (! $promoterCommissionUpdate) {
                        continue;
                    }

                    $saleItemCommissionPercentage = (float) $promoterCommissionUpdate->commission_percentage;

                    $saleItemFlatCommission = (float) $promoterCommissionUpdate->flat_commission;

                    $discountType = (float) $promoterCommissionUpdate->discount_type;

                    $paidCommissionAmount = (float) $promoterCommissionUpdate->commission_amount;

                    $paidAmount = (float) $promoterCommissionUpdate->amount;

                    $totalPricePaid = (float) $promoterCommissionUpdate->total_price_paid;

                    $returnAmount = 0.00;
                    if ($totalPricePaid > 0) {
                        $returnAmount = CommonFunctions::numberFormat(
                            $returnedItem->total_price_paid * $paidAmount / $totalPricePaid
                        );
                    }

                    $itemCommissionAmount = 0.00;
                    if ($paidAmount > 0) {
                        $itemCommissionAmount = CommonFunctions::numberFormat(
                            ($returnAmount * $paidCommissionAmount / $paidAmount) * -1,
                            6
                        );
                    }

                    if ($itemCommissionAmount > 0.00) {
                        continue;
                    }

                    $promoterCommissionUpdateQueries->addNew([
                        'promoter_commission_id' => $promoterCommission->id,
                        'affected_by_id' => $returnedItem->id,
                        'affected_by_type' => ModelMapping::SALE_RETURN_ITEM->name,
                        'commission_percentage' => $saleItemCommissionPercentage,
                        'flat_commission' => $saleItemFlatCommission,
                        'discount_type' => $discountType,
                        'commission_amount' => $itemCommissionAmount,
                        'department_id' => $promoterCommissionUpdate->department_id,
                        'location_id' => $promoterCommissionUpdate->location_id,
                        'brand_id' => $promoterCommissionUpdate->brand_id,
                        'amount' => -$returnAmount,
                        'total_price_paid' => -$returnedItem->total_price_paid,
                    ]);

                    $totalCommissionAmount += $itemCommissionAmount;
                    $totalReturnAmount += $returnAmount;
                }

                $commissionAmountRounding = RoundOffConfigurationToSixDigits::roundOffCalculationFor(
                    (string) $totalCommissionAmount
                );
                $totalCommissionAmount += $commissionAmountRounding;

                $totalReturnAmountRounding = RoundOffConfiguration::roundOffCalculationFor((string) $totalReturnAmount);
                $totalReturnAmountRounding *= -1;
                $totalReturnAmount += $totalReturnAmountRounding;

                $totalAmountRounding = RoundOffConfiguration::roundOffCalculationFor((string) $totalAmount);
                $totalAmount += $totalAmountRounding;

                $promoterCommissionQueries->updateCommissionAmount(
                    $promoterCommission,
                    $totalCommissionAmount,
                    $commissionAmountRounding,
                    $totalReturnAmount,
                    $totalReturnAmountRounding,
                    $totalAmount,
                    $totalAmountRounding
                );
            }

            $promoterCommissionRegenerationQueries = resolve(PromoterCommissionRegenerationQueries::class);
            $promoterCommissionRegenerationQueries->markAsCompleted(now()->format('Y-m-d H:i:s'));

            DB::commit();

            Log::channel('promoter_commission_generation')->info('promoter_commission_generation', [
                'The promoter commission has been completed. Date: ' . $this->commissionGenerationDate . ' Promoter Ids: ' . implode(
                    ',',
                    $this->promoterIds
                ),
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Promoter commission generation failed', [
                'date' => 'Date: ' . $this->commissionGenerationDate,
                'promoter_ids' => 'Promoter Ids: ' . implode(',', $this->promoterIds),
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

    public function saveSaleItemsCommission(
        Promoter $promoter,
        Collection $monthlySaleItems,
        float $monthlySalesAmount,
        int $promoterCommissionId
    ): array {
        $totalCommissionAmount = 0.00;
        $totalAmount = 0.00;
        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);

        foreach ($monthlySaleItems as $monthlySaleItem) {
            $sale = $monthlySaleItem->sale;
            $counterUpdate = $sale->counterUpdate;
            $counter = $counterUpdate->counter;
            $brandId = $monthlySaleItem->product->brand_id;
            $locationId = $counter->location_id;
            $departmentId = null;
            $commissionPercentage = 0.00;
            $flatCommission = 0.00;

            /** @var Employee $employee */
            $employee = $promoter->employee;

            /** @var Company $company */
            $company = $employee->company;

            switch ($company->commission_type_id) {
                case CommissionTypes::BY_PROMOTER:
                    $commissionPercentage = CommonFunctions::numberFormat(
                        (float) $promoter->default_commission_amount_percentage
                    );
                    $monthlySalesTarget = CommonFunctions::numberFormat((float) $promoter->monthly_sales_target);
                    $monthlyTargetCommissionPercentage = CommonFunctions::numberFormat(
                        (float) $promoter->monthly_target_commission_percentage
                    );

                    if (
                        $monthlySalesTarget > 0.00 &&
                        $monthlySalesAmount >= $monthlySalesTarget &&
                        $monthlyTargetCommissionPercentage > 0.00
                    ) {
                        $commissionPercentage = $monthlyTargetCommissionPercentage;
                    }

                    break;
                case CommissionTypes::BY_DEPARTMENT:
                    $departmentId = $monthlySaleItem->product->department_id;
                    $commissionPercentage = 0;

                    if ($departmentId) {
                        if ($monthlySaleItem->product->department->discount_type === DiscountTypes::FLAT->value) {
                            $flatCommission = (float) $monthlySaleItem->product->department->flat_commission;
                        }

                        if ($monthlySaleItem->product->department->discount_type === DiscountTypes::PERCENTAGE->value) {
                            $commissionPercentage = CommonFunctions::numberFormat(
                                (float) $monthlySaleItem->product->department->commission_percentage
                            );
                        }
                    }

                    break;
                default:
                    throw new Exception(sprintf(
                        'Unknown commission type id (# %s) found for promoter (# %s) in company (# %d).',
                        $company->commission_type_id->name,
                        $promoter->id,
                        $company->id
                    ));
            }

            $itemTotalPricePaid = CommonFunctions::numberFormat((float) $monthlySaleItem->total_price_paid);

            $itemCommissionAmount = 0.00;
            $amount = 0.00;
            if ($monthlySaleItem->promoters->count() > 0) {
                if (CommissionTypes::BY_DEPARTMENT === $company->commission_type_id && null !== $monthlySaleItem->product->department_id) {
                    if ($monthlySaleItem->product->department->discount_type === DiscountTypes::PERCENTAGE->value) {
                        $itemCommissionAmount = CommonFunctions::numberFormat(
                            $itemTotalPricePaid * ($commissionPercentage / 100) / $monthlySaleItem->promoters->count(),
                            6
                        );
                    }

                    if ($monthlySaleItem->product->department->discount_type === DiscountTypes::FLAT->value) {
                        $itemCommissionAmount = CommonFunctions::numberFormat(
                            $flatCommission / $monthlySaleItem->promoters->count(),
                            6
                        );
                    }
                }

                if (CommissionTypes::BY_PROMOTER === $company->commission_type_id) {
                    $itemCommissionAmount = CommonFunctions::numberFormat(
                        $itemTotalPricePaid * ($commissionPercentage / 100) / $monthlySaleItem->promoters->count(),
                        6
                    );
                }

                $amount = CommonFunctions::numberFormat(
                    $monthlySaleItem->total_price_paid / $monthlySaleItem->promoters->count()
                );
            }

            $promoterCommissionUpdateQueries->addNew([
                'promoter_commission_id' => $promoterCommissionId,
                'affected_by_id' => $monthlySaleItem->id,
                'affected_by_type' => ModelMapping::SALE_ITEM->name,
                'commission_percentage' => $commissionPercentage,
                'flat_commission' => $flatCommission,
                'discount_type' => null !== $monthlySaleItem->product->department_id ? $monthlySaleItem->product->department->discount_type : DiscountTypes::PERCENTAGE->value,
                'commission_amount' => $itemCommissionAmount,
                'department_id' => $departmentId,
                'brand_id' => $brandId,
                'location_id' => $locationId,
                'amount' => $amount,
                'total_price_paid' => $monthlySaleItem->total_price_paid,
            ]);

            $totalCommissionAmount += $itemCommissionAmount;
            $totalAmount += $amount;
        }

        return [$totalCommissionAmount, $totalAmount];
    }
}
