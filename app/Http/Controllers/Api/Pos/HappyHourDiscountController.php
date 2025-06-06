<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountListDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscount\Resources\HappyHourDiscountListApiResource;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountCheckService;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HappyHourDiscountController extends Controller
{
    public function getProductTypes(): array
    {
        return [
            'product_types' => ProductTypes::getList(),
        ];
    }

    public function getPaginateHappyHourDiscountList(
        HappyHourDiscountListDataForPos $happyHourDiscountListDataForPos,
        Request $request
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $checkSaleDetailsService = resolve(CheckSaleDetailsService::class);
        $location = $checkSaleDetailsService->getCurrentLocation($cashier);

        $filterData = [
            'per_page' => $happyHourDiscountListDataForPos->per_page,
            'company_id' => $companyId,
            'location_id' => $location->id,
            'product_type_id' => $happyHourDiscountListDataForPos->product_type_id,
            'search_text' => $happyHourDiscountListDataForPos->search_text,
            'sort_by' => $happyHourDiscountListDataForPos->sort_by,
            'sort_direction' => $happyHourDiscountListDataForPos->sort_direction,
            'after_updated_at' => $happyHourDiscountListDataForPos->after_updated_at,
        ];

        $happyHourDiscountQueries = resolve(HappyHourDiscountQueries::class);
        $happyHourDiscounts = $happyHourDiscountQueries->getPaginatedHappyHourDiscounts($filterData);

        return [
            'happy_hour_discounts' => HappyHourDiscountListApiResource::collection($happyHourDiscounts),
            'total_records' => $happyHourDiscounts->total(),
            'last_page' => $happyHourDiscounts->lastPage(),
            'current_page' => $happyHourDiscounts->currentPage(),
            'per_page' => $happyHourDiscounts->perPage(),
        ];
    }

    public function store(HappyHourDiscountDataForPos $happyHourDiscountDataForPos, Request $request): void
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $locationQueries = resolve(LocationQueries::class);
        $locationId = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId())->getKey();

        $happyHourDiscountCheckService = resolve(HappyHourDiscountCheckService::class);
        $happyHourDiscountCheckService->setDetails();
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, $companyId, $cashier);

        DB::beginTransaction();

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        try {
            $happyHourDiscountService = resolve(HappyHourDiscountService::class);
            $happyHourDiscount = $happyHourDiscountService->addHappyHourDiscount(
                $happyHourDiscountDataForPos,
                $companyId,
                $counterUpdateId,
                $locationId,
                $cashier
            );

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::BOOKING_PAYMENT->value,
                $happyHourDiscount->id,
                ModelMapping::HAPPY_HOUR_DISCOUNT->name,
                $happyHourDiscountDataForPos->store_manager_authorization_code
            );

            $happyHourDiscountService->saveHappyHourDiscountMismatches(
                $happyHourDiscountCheckService->happyHourDiscountMismatches,
                $happyHourDiscount
            );

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Cashier-Happy-Hour', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            abort(412, 'An error occurred. Please try again.');
        }
    }
}
