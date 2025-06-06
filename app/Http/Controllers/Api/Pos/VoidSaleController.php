<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sale\Resources\PosVoidedSalesResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\VoidSale\DataObjects\PaginatedVoidedSalesDataForPos;
use App\Domains\VoidSale\DataObjects\PosVoidSaleData;
use App\Domains\VoidSale\Services\VoidSaleService;
use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Sale;
use App\Models\VoidSale;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoidSaleController extends Controller
{
    public function store(Request $request, int $saleId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $posVoidSaleData = new PosVoidSaleData(
            voided_by_store_manager_id: (int) $request->voided_by_store_manager_id,
            passcode: $request->passcode,
            void_sale_reason_id: (int) $request->void_sale_reason_id,
            store_manager_authorization_code: $request->store_manager_authorization_code,
        );

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $request->validate($posVoidSaleData->rules($companyId));

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getRegularOrLayawayOrCreditSaleByIdWithItemsAndItemUnits($saleId);

        $saleMismatches = collect([]);

        $this->checkRequestDetails($posVoidSaleData, $cashier, $sale, $companyId, $saleMismatches);

        DB::beginTransaction();

        try {
            $voidSaleService = resolve(VoidSaleService::class);
            $voidSale = $voidSaleService->saveVoidDetails($posVoidSaleData, $sale->id, $companyId);
            $voidSaleService->checkAndRevertLoyaltyPoints($sale, $voidSale);
            $voidSaleService->checkAndRevertCreditNote($sale->id, $voidSale->id);
            $voidSaleService->checkAndRevertBookingPayment($sale->id, $voidSale->id);
            $voidSaleService->checkAndRevertVouchersGenerated($sale->id, $location->id);
            $voidSaleService->checkAndRevertCashback($sale->id, $voidSale->id);
            $voidSaleService->checkAndRevertGiftCard($sale->id, $voidSale->id);
            $voidSaleService->checkAndRevertUsedVoucher($sale->id, $location->id);

            $sale = $saleQueries->loadVoidSaleRelations($sale);
            $voidSaleService->revertUsedLoyaltyPoints(
                $sale->id,
                ModelMapping::SALE->name,
                $sale->member,
                $voidSale,
            );
            $voidSaleService->revertUsedItemLoyaltyPoints($sale, $voidSale);

            $voidSaleService->updateInventory($sale, $voidSale, $cashier, $location->id);

            $saleQueries->markAsVoid($sale);

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::VOID_SALE->value,
                $voidSale->id,
                ModelMapping::VOID_SALE->name,
                $posVoidSaleData->store_manager_authorization_code
            );

            $this->saveSaleMismatches($voidSale, $saleMismatches);

            DB::commit();

            $sale = $saleQueries->loadVoidSaleRelations($sale);

            if ($sale->member_id) {
                MemberUpdatePointsAndTotalSalesJob::dispatch($sale->member_id)->onQueue('medium');
            }

            return [
                'sale' => new PosVoidedSalesResource($sale),
            ];
        } catch (Throwable $throwable) {
            Log::error('Void Sale', [
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

    public function saveSaleMismatches(VoidSale $voidSale, Collection $saleMismatches): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($saleMismatches as $saleMismatch) {
            $posMismatchQueries->addNew($voidSale, $saleMismatch);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaginatedVoidedSales(
        Request $request,
        PaginatedVoidedSalesDataForPos $paginatedVoidedSalesDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'member_id' => $paginatedVoidedSalesDataForPos->member_id,
            'employee_id' => $paginatedVoidedSalesDataForPos->employee_id,
            'is_user' => $paginatedVoidedSalesDataForPos->is_user,
            'from_date' => $paginatedVoidedSalesDataForPos->from_date,
            'to_date' => $paginatedVoidedSalesDataForPos->to_date,
            'search_text' => $paginatedVoidedSalesDataForPos->search_text,
            'after_updated_at' => $paginatedVoidedSalesDataForPos->after_updated_at,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $sales = $saleQueries->getPaginatedVoidedSales($filterData, $location->id);

        return [
            'sales' => PosVoidedSalesResource::collection($sales),
            'total_records' => $sales->total(),
            'last_page' => $sales->lastPage(),
            'current_page' => $sales->currentPage(),
            'per_page' => $sales->perPage(),
        ];
    }

    private function checkRequestDetails(
        PosVoidSaleData $posVoidSaleData,
        Cashier $cashier,
        Sale $sale,
        int $companyId,
        Collection $saleMismatches
    ): void {
        $voucherQueries = resolve(VoucherQueries::class);

        if ($voucherQueries->checkGeneratedVoucherIsUsed($sale->id)) {
            abort(
                412,
                'I apologize, but it seems that this voucher has already been used for another transaction and is no longer eligible for voiding or refunding..'
            );
        }

        if ($sale->getSaleItems()->firstWhere('returned_quantity', '>', 0) !== null) {
            abort(412, 'A returned or sale return is not voidable.');
        }

        if ($cashier->getCounterUpdateId() !== $sale->getCounterUpdateId()) {
            abort(412, 'You can only void the current open counter sale.');
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);

        $storeManager = $storeManagerQueries->getById($posVoidSaleData->voided_by_store_manager_id, $companyId);

        if ($posVoidSaleData->passcode !== $storeManager->passcode) {
            abort(412, 'Wrong passcode.');
        }

        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $saleMismatches,
            $posVoidSaleData->voided_by_store_manager_id,
            $posVoidSaleData->store_manager_authorization_code,
            now()->format('Y-m-d H:i:s')
        );
    }
}
