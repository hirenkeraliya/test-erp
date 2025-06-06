<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\Counter\DataObjects\StoreManagerApiDayCloseCounterData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Resources\StoreManagerAppDayCloseCounterUpdateListResource;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\CounterUpdate\Services\CounterUpdateService;
use App\Domains\Denomination\DenominationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DayCloseController extends Controller
{
    public function getCountersForDayClose(
        Request $request,
        StoreManagerApiDayCloseCounterData $storeManagerApiDayCloseCounterData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        /** @var int $locationId */
        $locationId = $storeManagerApiDayCloseCounterData->store_id ?? $storeManagerApiDayCloseCounterData->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'The Provided Store is not accepted or allowed.');
        }

        $filterData = [
            'location_id' => $locationId,
            'status' => $storeManagerApiDayCloseCounterData->status,
            'search_text' => $storeManagerApiDayCloseCounterData->search_text,
        ];

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $lastStoreDayClose = $storeDayCloseQueries->getLastDayClose($locationId);

        $dayCloseCounters = $counterUpdateQueries->getByDayCloseAndStoreByType(
            $filterData,
            $companyId,
            $lastStoreDayClose
        );

        return [
            'data' => StoreManagerAppDayCloseCounterUpdateListResource::collection($dayCloseCounters),
        ];
    }

    public function closeCounter(
        CloseCounterDataForStoreManager $closeCounterData,
        int $locationId,
        int $id,
        Request $request
    ): void {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdate = $counterUpdateQueries->findByIdAndFilterByStore($locationId, $companyId, $id);

        if (! $counterUpdate) {
            abort(412, 'The Provided Store is not accepted or allowed.');
        }

        $closeCounterService = resolve(CloseCounterService::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        $cashier = $cashierQueries->findByCounterUpdateId($counterUpdate->getKey());
        if (! $cashier) {
            abort(412, 'The counter has not been opened yet.');
        }

        $counterClosingDetails = $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate);

        $closeCounterService->checkRequestDetails($closeCounterData, $counterClosingDetails, 412);
        DB::beginTransaction();

        try {
            $closeCounterService->closeCounter(
                $closeCounterData,
                $counterUpdate,
                $counterClosingDetails,
                ModelMapping::STORE_MANAGER->name,
                $storeManager->id
            );

            $counterQueries->unsetCounterUpdateId($counter);

            $cashierQueries->unsetCounterUpdateId($cashier);

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('StoreManager-CloseCounter', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function counterDetails(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
            'id' => ['required', 'integer'],
        ]);

        $validatedData['location_id'] = $validatedData['store_id'] ?? $validatedData['location_id'];

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            (int) $validatedData['location_id']
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'The Provided Store is not accepted or allowed.');
        }

        $locationId = (int) $validatedData['location_id'];
        $counterUpdateId = (int) $validatedData['id'];

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdate = $counterUpdateQueries->findByIdAndFilterByStore($locationId, $companyId, $counterUpdateId);

        if (! $counterUpdate) {
            abort(412, 'The Selected Store has invalid counter.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $denominationQueries = resolve(DenominationQueries::class);
        $denominations = $denominationQueries->getByCompanyId($companyId);

        $denominations = $denominations->map(function ($denomination) {
            $denomination->quantity = 0;

            return $denomination;
        });

        $counterUpdateService = resolve(CounterUpdateService::class);

        /** @var CounterUpdate $counterUpdate */
        return [
            'data' => array_merge(
                [
                    'mismatch_amount' => $counterUpdate->getMismatchAmount(),
                    'amount_mismatch_reason' => $counterUpdate->getAmountMismatchReason(),
                    'denominations' => $denominations->toArray(),
                ],
                $counterUpdateService->prepareCounterDetails($counterUpdate, $locationId)
            ),
        ];
    }

    public function dayClose(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
        ]);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            (int) $locationId,
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'The Provided Store is not accepted or allowed.');
        }

        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $lastStoreDayClose = $storeDayCloseQueries->getLastDayClose((int) $locationId);

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->findByIdWithReceiptFooterDisclaimerAndCreatedAt((int) $locationId);

        if (! $location) {
            abort(412, 'The Store is not found.');
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $totalOpenCounters = $counterUpdateQueries->getOpenCountersCountFilterByStoreAndDates(
            (int) $locationId,
            $lastStoreDayClose
        );

        if ($totalOpenCounters > 0) {
            abort(
                412,
                $totalOpenCounters . ' counters are still open. Please close all the counters for Day Close first.'
            );
        }

        $requestLock = Cache::lock('day_close_operation_working_' . (int) $locationId);

        if ($requestLock->get()) {
            $storeDayCloseService = resolve(StoreDayCloseService::class);
            DB::beginTransaction();

            try {
                $storeDayClose = $storeDayCloseService->addStoreDayClose(
                    $counterUpdateQueries,
                    $storeDayCloseQueries,
                    $location,
                    $lastStoreDayClose,
                    $storeManager->getKey()
                );

                DB::commit();

                return [
                    'store_day_close' => $storeDayCloseQueries->loadRelations($storeDayClose),
                    'store_receipt_footer' => $location->receipt_footer,
                    'store_disclaimer' => $location->disclaimer,
                ];
            } catch (Throwable $throwable) {
                Log::error('StoreManager-App-Day-Close', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);

                DB::rollBack();

                abort(412, 'An error occurred. Please try again.');
            } finally {
                $requestLock->release();
            }
        } else {
            abort(412, 'Day close operation already in progress.');
        }
    }
}
