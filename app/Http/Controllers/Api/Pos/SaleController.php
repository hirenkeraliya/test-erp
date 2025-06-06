<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Common\Jobs\AutomationJob;
use App\Domains\Company\CompanyQueries;
use App\Domains\CreditNote\Resources\PosCreditNoteResource;
use App\Domains\CreditNote\Services\CreditNoteService;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\Member\Jobs\NewMemberBenefitsJob;
use App\Domains\MergeProductTransaction\MergeProductTransactionQueries;
use App\Domains\PosMismatch\Services\PosMismatchService;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\DataObjects\PaginatedRegularAndCompletedSalesDataForPos;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Mail\SendSaleConfirmationUserMail;
use App\Domains\Sale\Resources\PosPaginatedSaleResource;
use App\Domains\Sale\Resources\PosPromoterSaleListResource;
use App\Domains\Sale\Resources\PosSaleDetailsResource;
use App\Domains\Sale\Resources\PosSaleListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaveSaleDetailsService;
use App\Domains\Sale\Services\SaveSaleReturnDetailsService;
use App\Domains\SaleReturn\Resources\PosSaleReturnResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SaleController extends Controller
{
    public function saveDetails(SaleData $saleData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $companyQueries = resolve(CompanyQueries::class);
        $companyDetails = $companyQueries->getCompanyDetails($companyId);

        $items = collect($saleData->items);
        $mergeProductTransactionQueries = resolve(MergeProductTransactionQueries::class);
        $mergedProducts = $mergeProductTransactionQueries->getByOldProductId($items->pluck('id')->toArray());
        $mergedProductIds = $mergedProducts->pluck('new_product_id')->toArray();

        $productIds = array_merge($mergedProductIds, $items->pluck('id')->toArray());

        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getByIdsWithBrandAndCategories($productIds, $companyId);

        $items->transform(function (array $item) use ($mergedProducts): array {
            $mergedProduct = $mergedProducts->firstWhere('old_product_id', $item['id']);
            if ($mergedProduct) {
                $item['id'] = $mergedProduct->new_product_id;
            }

            return $item;
        });

        $batchQueries = resolve(BatchQueries::class);
        $batches = $batchQueries->getByProductIds($items->pluck('id')->toArray(), $companyId);

        $checkSaleDetailsService = resolve(CheckSaleDetailsService::class);
        $location = $checkSaleDetailsService->getCurrentLocation($cashier);

        $appVersion = $this->getAppVersion($request->header());

        $checkSaleDetailsService->setDetails(
            $saleData,
            $products,
            $items,
            $batches,
            $location,
            $cashier,
            $companyId,
            $appVersion
        );

        $checkSaleDetailsService->checkRequestDetails((bool) $companyDetails->allow_exchange_to_different_store);

        $memberId = $checkSaleDetailsService->member?->id;

        DB::beginTransaction();

        try {
            $saveSaleReturnDetailsService = resolve(SaveSaleReturnDetailsService::class);
            $saleReturn = $saveSaleReturnDetailsService->saveSaleReturnDetails(
                $cashier,
                $checkSaleDetailsService,
                $memberId
            );

            $saveSaleDetailsService = resolve(SaveSaleDetailsService::class);
            $sale = $saveSaleDetailsService->saveDetails($cashier, $checkSaleDetailsService, $memberId, $saleReturn);

            DB::commit();

            if ($sale) {
                $saleQueries = resolve(SaleQueries::class);
                $sale = $saleQueries->loadRelations($sale);

                /** @var Member $member */
                $member = $sale->member;

                $currencyQueries = resolve(CurrencyQueries::class);
                $currency = $currencyQueries->getByCompanyId($companyId);

                AutomationJob::dispatch($member);

                if ($companyDetails->send_sale_email_to_member && null !== $member && $member->email) {
                    Mail::to($member->email)->send(new SendSaleConfirmationUserMail($sale, $currency->getSymbol()));
                }
            }

            $creditNoteService = resolve(CreditNoteService::class);
            $creditNotes = $creditNoteService->getCreditNotes($saleReturn, $saleData, $location->id);

            if ($sale && $sale->has_mismatch) {
                $messages = $sale->mismatches->pluck('message')->toArray();
                $posMismatchService = resolve(PosMismatchService::class);
                $posMismatchService->logMismatchEntries(
                    'New Sale Mismatches',
                    $sale->id,
                    $messages,
                    $sale->offline_sale_id
                );
            }

            if ($saleReturn && $saleReturn->has_mismatch) {
                $messages = $saleReturn->mismatches->pluck('message')->toArray();
                $posMismatchService = resolve(PosMismatchService::class);
                $posMismatchService->logMismatchEntries(
                    'New Sale Return Mismatches',
                    $saleReturn->id,
                    $messages,
                    $saleReturn->offline_sale_return_id
                );
            }

            if ($checkSaleDetailsService->member) {
                MemberUpdatePointsAndTotalSalesJob::dispatch((int) $checkSaleDetailsService->member->id)->onQueue(
                    'medium'
                );

                NewMemberBenefitsJob::dispatch((int) $checkSaleDetailsService->member->id, $location->id)->onQueue(
                    'medium'
                );
            }

            if (config(
                'services.share_sale_details_to_third_party.share_sale_details_to_third_party_enabled'
            ) && $sale) {
                $saveSaleDetailsService->shareSaleDetailsThirdParty($sale);
            }

            return [
                'sale' => $sale ? new PosSaleListResource($sale) : null,
                'sale_return' => $saleReturn ? new PosSaleReturnResource($saleReturn) : null,
                'credit_notes' => PosCreditNoteResource::collection($creditNotes),
            ];
        } catch (Throwable $throwable) {
            Log::error('Cashier-Sales', [
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

    /**
     * @return array<string, mixed>
     */
    public function getPaginatedRegularAndCompletedSales(
        Request $request,
        PaginatedRegularAndCompletedSalesDataForPos $paginatedRegularAndCompletedSalesDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'per_page' => $paginatedRegularAndCompletedSalesDataForPos->per_page,
            'member_id' => $paginatedRegularAndCompletedSalesDataForPos->member_id,
            'employee_id' => $paginatedRegularAndCompletedSalesDataForPos->employee_id,
            'counter_id' => $paginatedRegularAndCompletedSalesDataForPos->counter_id,
            'from_date' => $paginatedRegularAndCompletedSalesDataForPos->from_date,
            'to_date' => $paginatedRegularAndCompletedSalesDataForPos->to_date,
            'search_text' => $paginatedRegularAndCompletedSalesDataForPos->search_text,
            'sort_by' => $paginatedRegularAndCompletedSalesDataForPos->sort_by,
            'sort_direction' => $paginatedRegularAndCompletedSalesDataForPos->sort_direction,
            'after_updated_at' => $paginatedRegularAndCompletedSalesDataForPos->after_updated_at,
            'status_id' => $paginatedRegularAndCompletedSalesDataForPos->status_id,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getPaginatedRegularAndCompletedLayawaySalesWithItemsPaymentsAndMismatches(
            $filterData,
            $location->id
        );

        return [
            'sales' => PosPaginatedSaleResource::collection($sales),
            'total_records' => $sales->total(),
            'last_page' => $sales->lastPage(),
            'current_page' => $sales->currentPage(),
            'per_page' => $sales->perPage(),
        ];
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getSalesByPromoter(Request $request, int $promoterId): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSalesByPromoter($location->id, $promoterId, $afterUpdatedAt);

        return [
            'promoter_sales' => PosPromoterSaleListResource::collection($sales),
        ];
    }

    /**
     * @return array<string, PosSaleDetailsResource>
     */
    public function getSaleDetails(Request $request, int|string $saleId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getSaleWithRelations($companyId, $saleId);

        return [
            'sale' => new PosSaleDetailsResource($sale),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getPriceOverrideTypes(): array
    {
        return [
            'price_override_types' => PriceOverrideTypes::getList(),
        ];
    }

    public function getSaleStatuses(): array
    {
        return [
            SaleStatus::getFormattedArrayForPos(SaleStatus::REGULAR_SALE->value),
            SaleStatus::getFormattedArrayForPos(SaleStatus::COMPLETE_LAYAWAY_SALE->value),
            SaleStatus::getFormattedArrayForPos(SaleStatus::COMPLETE_CREDIT_SALE->value),
        ];
    }

    private function getAppVersion(array $requestHeaders): int
    {
        $appVersion = 0;
        if (array_key_exists('app-version', $requestHeaders)) {
            $appVersion = $requestHeaders['app-version'][0];
        }

        return (int) Str::remove('.', $appVersion);
    }
}
