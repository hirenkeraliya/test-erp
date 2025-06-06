<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\CancelCreditSale\Resources\PosCancelCreditSalesResource;
use App\Domains\CancelCreditSale\Services\CancelCreditSaleService;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sale\DataObjects\CancelCreditSaleData;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\DataObjects\PendingCreditSalesDataForPos;
use App\Domains\Sale\Resources\PosCreditSaleListResource;
use App\Domains\Sale\Resources\PosPendingCreditSaleListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CompleteCreditSaleService;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\LayawayAndCreditSaleCashbackService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\Services\LayawayAndCreditSaleGenerateVoucherService;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;
use Throwable;

class CreditSaleController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getPendingCreditSales(
        Request $request,
        PendingCreditSalesDataForPos $pendingCreditSalesDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'member_id' => $pendingCreditSalesDataForPos->member_id,
            'employee_id' => $pendingCreditSalesDataForPos->employee_id,
            'from_date' => $pendingCreditSalesDataForPos->from_date,
            'to_date' => $pendingCreditSalesDataForPos->to_date,
            'search_text' => $pendingCreditSalesDataForPos->search_text,
            'after_updated_at' => $pendingCreditSalesDataForPos->after_updated_at,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $pendingCreditSales = $saleQueries->getPendingCreditSalesWithRelations($filterData, $location->id);

        return [
            'pending_credit_sales' => PosCreditSaleListResource::collection($pendingCreditSales),
        ];
    }

    /**
     * @return array<string, PosPendingCreditSaleListResource>
     */
    public function getPendingCreditSale(Request $request, int|string $saleId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getPendingCreditSaleByIdWithRelations($saleId, $location->id);

        return [
            'sale' => new PosPendingCreditSaleListResource($sale),
        ];
    }

    public function completeCreditSale(
        CompleteCreditSaleData $completeCreditSaleData,
        Request $request,
        int $saleId
    ): array {
        $saleMismatches = collect([]);

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getSaleByIdWithSaleItems($saleId);

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        /** @var CompanySetting $companySetting */
        $companySetting = $company->companySetting;

        $companyId = $location->company_id;
        $completeCreditSaleService = resolve(CompleteCreditSaleService::class);
        $completeCreditSaleService->checkRequestDetails(
            $completeCreditSaleData,
            $sale,
            $saleMismatches,
            $companyId,
            $location->id
        );

        $payments = collect($completeCreditSaleData->payments);

        $saleFinalAmount = $payments->sum('amount');

        $happenedAt = $completeCreditSaleData->happened_at ?? now()->format('Y-m-d H:i:s');
        $generateLoyaltyPointService = resolve(GenerateLoyaltyPointsService::class);
        if ($generateLoyaltyPointService->hasGenerateLoyaltyPointsForCreditSale($completeCreditSaleData)) {
            $generateLoyaltyPointService->setDetails($completeCreditSaleData->loyalty_points, $companyId);

            $loyaltyPointsMismatches = $generateLoyaltyPointService->checkCreditSaleLoyaltyPoints(
                $saleFinalAmount,
                $sale->member_id,
                $sale,
                $happenedAt
            );

            $saleMismatches = $saleMismatches->merge($loyaltyPointsMismatches);
        }

        $layawayAndCreditSaleGenerateVoucherService = resolve(LayawayAndCreditSaleGenerateVoucherService::class);
        if ($completeCreditSaleData->vouchers instanceof DataCollection) {
            $layawayAndCreditSaleGenerateVoucherService->setDetails($completeCreditSaleData, $sale, $companyId);

            $subtotal = $saleFinalAmount + $sale->total_amount_paid;
            $layawayAndCreditSaleGenerateVoucherService->checkVouchers($subtotal, $saleMismatches);
        }

        $checkCompanySettingService = resolve(CheckCompanySettingService::class);
        $checkCompanySettingService->setDetails($companySetting);
        $checkCompanySettingService->checkCompleteCreditSaleSettings($completeCreditSaleData, $saleMismatches);

        $layawayAndCreditSaleCashbackService = resolve(LayawayAndCreditSaleCashbackService::class);
        if ($layawayAndCreditSaleCashbackService->hasCashback($completeCreditSaleData)) {
            /** @var int $cashbackId */
            $cashbackId = $completeCreditSaleData->cashback_id;
            $layawayAndCreditSaleCashbackService->setDetails($cashbackId, $companyId);

            $subtotal = $saleFinalAmount + $sale->total_amount_paid;
            $layawayAndCreditSaleCashbackService->checkForApplicability(
                $subtotal,
                $completeCreditSaleData,
                $saleMismatches,
                $location,
                $sale
            );
        }

        DB::beginTransaction();

        try {
            $saleItemQueries = resolve(SaleItemQueries::class);

            $generateLoyaltyPointService->generateLoyaltyPointsForCreditSale(
                $completeCreditSaleData,
                $sale,
                $companySetting,
                $companyId,
                $saleFinalAmount,
                $sale->member_id,
            );

            if ($completeCreditSaleData->vouchers instanceof DataCollection) {
                $layawayAndCreditSaleGenerateVoucherService->saveVouchers($sale, $cashier);
            }

            /** @var int $counterUpdateId */
            $counterUpdateId = $cashier->getCounterUpdateId();

            if ($layawayAndCreditSaleCashbackService->hasCashback($completeCreditSaleData)) {
                $layawayAndCreditSaleCashbackService->saveCashback(
                    $sale,
                    $completeCreditSaleData,
                    $counterUpdateId
                );
            }

            $completeCreditSaleService->saveDetails(
                $completeCreditSaleData,
                $sale,
                $payments,
                $saleMismatches,
                $counterUpdateId,
                $companyId,
                $location->id
            );

            $isCompletedCreditSale = false;
            if (($sale->credit_pending_amount - $payments->sum('amount')) <= 0) {
                $isCompletedCreditSale = true;
            }

            $saleItemQueries->updateCreditAmountOf($sale, $payments->sum('amount'), $isCompletedCreditSale);
            $sale = $saleQueries->updateCreditAmountOf($sale, $payments, $happenedAt);

            DB::commit();

            $sale = $saleQueries->loadRelations($sale);

            if ($sale->member_id) {
                MemberUpdatePointsAndTotalSalesJob::dispatch($sale->member_id)->onQueue('medium');
            }

            return [
                'sale' => new PosCreditSaleListResource($sale),
            ];
        } catch (Throwable $throwable) {
            Log::error('Complete Credit Sale', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function getTotalCreditPendingAmount(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $totalCreditSalePendingAmount = $saleQueries->totalCreditSalePendingAmount($companyId, $location->id);

        return [
            'total_credit_sale_pending_amount' => CommonFunctions::numberFormat($totalCreditSalePendingAmount),
        ];
    }

    public function cancelCreditSale(
        CancelCreditSaleData $cancelCreditSaleData,
        Request $request,
        int $saleId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($counterUpdateId);

        $companyId = CommonFunctions::getCashierCompanyId($cashier);
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getPendingCreditSaleByIdAndRelations($saleId);

        $saleMismatches = collect([]);

        $cancelCreditSaleService = resolve(CancelCreditSaleService::class);
        $cancelCreditSaleService->checkRequestDetails(
            $cancelCreditSaleData,
            $sale,
            $location,
            $companyId,
            $saleMismatches
        );

        DB::beginTransaction();

        try {
            $cancelCreditSaleService->saveDetails($cancelCreditSaleData, $sale, $counterUpdateId, $location, $cashier);

            $saleQueries->markAsCancelCredit($sale);

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::CANCEL_CREDIT_SALE->value,
                $sale->id,
                ModelMapping::SALE->name,
                $cancelCreditSaleData->store_manager_authorization_code
            );

            $this->saveSaleMismatches($sale, $saleMismatches);

            DB::commit();

            $sale = $saleQueries->loadCancelCreditSaleRelations($sale);

            if ($sale->member_id) {
                MemberUpdatePointsAndTotalSalesJob::dispatch($sale->member_id)->onQueue('medium');
            }

            return [
                'sale' => new PosCancelCreditSalesResource($sale),
            ];
        } catch (Throwable $throwable) {
            Log::error('Cancel Credit Sale', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    private function saveSaleMismatches(Sale $sale, Collection $saleMismatches): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($saleMismatches as $saleMismatch) {
            $posMismatchQueries->addNew($sale, $saleMismatch);
        }
    }
}
