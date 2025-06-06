<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\Resources\CashierBasicDetailsResource;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Resources\CompanyBasicDetailForMeResource;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterData;
use App\Domains\Counter\DataObjects\OpenCounterData;
use App\Domains\Counter\DataObjects\OpenCounterStatusDataForPos;
use App\Domains\Counter\DataObjects\PaginatedLastThirtyDaysCloseCountersDataForPos;
use App\Domains\Counter\Resources\PosCounterMeApiResource;
use App\Domains\Counter\Resources\PosCounterResource;
use App\Domains\Counter\Resources\PosOpenCounterDetailsResource;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Resources\CloseCounterResource;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Resources\LocationStoreBasicDetailsResource;
use App\Domains\Sale\Resources\PosClosedCounterSaleResource;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CounterController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getStoreCounters(
        CashierQueries $cashierQueries,
        CounterQueries $counterQueries,
        int $locationId,
        Request $request
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashierQueries->isAuthorizedToSelectedLocation($cashier, $locationId, $companyId)) {
            abort(412, 'Store not found.');
        }

        $counters = $counterQueries->getCounterListOfSelectedLocation($locationId, $companyId);

        return [
            'counters' => PosCounterResource::collection($counters),
        ];
    }

    public function openCounter(OpenCounterData $openCounterData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counter = $counterQueries->getById((int) $openCounterData->counter_id, $companyId);

        $appVersion = array_key_exists('app-version', $request->headers->all()) ? current(
            $request->headers->all()['app-version']
        ) : null;

        $this->checkRequestDetails($counter, $cashier, $openCounterData);

        DB::beginTransaction();

        try {
            $counterUpdateId = $counterUpdateQueries->addNew($openCounterData, $cashier->getKey());

            $cashierQueries = resolve(CashierQueries::class);
            $cashierQueries->setCounterUpdateId($cashier, $counterUpdateId);

            $counterQueries = resolve(CounterQueries::class);
            $counterQueries->setCounterUpdateId($counter, $counterUpdateId);

            $counterQueries->setCounterAppVersion($counter, (string) $appVersion);

            DB::commit();

            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $cashier->getCounterUpdate();

            $location = $counter->location;

            /** @var Employee $employee */
            $employee = $cashier->getEmployee();

            /** @var Company $company */
            $company = $employee->company;

            return [
                'cashier' => new CashierBasicDetailsResource($cashier),
                'store' => $location ? new LocationStoreBasicDetailsResource($location) : null,
                'location' => $location ? new LocationStoreBasicDetailsResource($location) : null,
                'counter' => new PosCounterMeApiResource($counterUpdate),
                'company' => new CompanyBasicDetailForMeResource($company),
            ];
        } catch (Throwable $throwable) {
            Log::error('Cashier-OpenCounter', [
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
     * @return array<string, PosOpenCounterDetailsResource>
     */
    public function getCurrentlyOpenCounterDetails(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $counterQueries = resolve(CounterQueries::class);
        $counter = $counterQueries->getDetailsWithCounterUpdateByCounterUpdateId($cashier->getCounterUpdateId());

        return [
            'counter' => new PosOpenCounterDetailsResource($counter),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getCurrentCounterClosingDetails(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'This account currently has no open counters.');
        }

        $cashierQueries = resolve(CashierQueries::class);
        $cashier = $cashierQueries->loadDetailsForCounterCloseApi($cashier);

        $closeCounterService = resolve(CloseCounterService::class);

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $cashier->getCounterUpdate();

        $openedAt = $counterUpdate->opened_by_pos_at;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        return [
            'counter_closing_details' => array_merge([
                'cashier_name' => $employee->getFullName(),
                'counter_name' => $counter->getName(),
                'opening_date_time' => $openedAt,
            ], $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate)),
        ];
    }

    public function closeCounter(Request $request, CloseCounterData $closeCounterData): void
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $cashierQueries = resolve(CashierQueries::class);
        $cashier = $cashierQueries->loadDetailsForCounterCloseApi($cashier);

        $counterUpdate = $cashier->counterUpdate;

        if (! $counterUpdate instanceof CounterUpdate) {
            abort(412, 'There is no Open Counter under this account.');
        }

        $closeCounterService = resolve(CloseCounterService::class);
        $closeCounterService->checkCloseCounterDetails($counterUpdate, $closeCounterData);

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        $counterClosingDetails = $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate);
        $closeCounterService->checkRequestDetails($closeCounterData, $counterClosingDetails, 412);

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        DB::beginTransaction();

        try {
            $closeCounterService->closeCounter(
                $closeCounterData,
                $counterUpdate,
                $counterClosingDetails,
                ModelMapping::CASHIER->name,
                $cashier->id
            );

            $counterQueries->unsetCounterUpdateId($counter);
            $cashierQueries->unsetCounterUpdateId($cashier);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Cashier-CloseCounter', [
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
    public function getPaginatedLastThirtyDaysClosedCounters(
        Request $request,
        PaginatedLastThirtyDaysCloseCountersDataForPos $paginatedLastThirtyDaysCloseCountersDataForPos
    ): array {
        $filteredData = [
            'per_page' => $paginatedLastThirtyDaysCloseCountersDataForPos->per_page,
            'search_text' => $paginatedLastThirtyDaysCloseCountersDataForPos->search_text,
            'sort_by' => $paginatedLastThirtyDaysCloseCountersDataForPos->sort_by,
            'sort_direction' => $paginatedLastThirtyDaysCloseCountersDataForPos->sort_direction,
            'after_updated_at' => $paginatedLastThirtyDaysCloseCountersDataForPos->after_updated_at,
        ];

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->counter_update_id);

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdates = $counterUpdateQueries->getPaginatedLastThirtyDaysClosedCountersForPos(
            $filteredData,
            $companyId,
            $location->id
        );

        return [
            'closed_counter' => CloseCounterResource::collection($counterUpdates),
            'total_records' => $counterUpdates->total(),
            'last_page' => $counterUpdates->lastPage(),
            'current_page' => $counterUpdates->currentPage(),
            'per_page' => $counterUpdates->perPage(),
        ];
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function closedCounterSales(Request $request, int $counterUpdateId): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;
        /** @var Cashier $cashier */
        $cashier = $request->user();

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $companyId = $counterUpdateQueries->getCompanyIdByCounterUpdateId($counterUpdateId);

        if ($employee->company_id !== $companyId) {
            abort(412, 'The Counter Update Id does not match with this company');
        }

        $counterUpdate = $counterUpdateQueries->getByIdWithClosedAtColumn($counterUpdateId);

        if (! $counterUpdate->closed_at) {
            abort(412, 'Only the Close Counter update ID is allowed.');
        }

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSalesByCounterUpdateId($counterUpdateId, $afterUpdatedAt);

        return [
            'closed_counter_sales' => PosClosedCounterSaleResource::collection($sales),
        ];
    }

    public function getCounterOpenStatus(OpenCounterStatusDataForPos $openCounterStatusDataForPos): array
    {
        if (! $openCounterStatusDataForPos->counter_id && ! $openCounterStatusDataForPos->opened_by_pos_at && ! $openCounterStatusDataForPos->counter_update_id) {
            abort(
                412,
                'Unable to proceed with the request. Please provide values for either counter_id and opened_by_pos_at, or counter_update_id, in order to complete the operation.'
            );
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdate = $counterUpdateQueries->getByIdOrByCounterIdAndOpenedByPosAt(
            (int) $openCounterStatusDataForPos->counter_update_id,
            $openCounterStatusDataForPos->counter_id,
            $openCounterStatusDataForPos->opened_by_pos_at,
        );

        if (! $counterUpdate) {
            return [
                'isCounterOpened' => false,
                'isCounterClosed' => false,
            ];
        }

        if (! $counterUpdate->closed_by_pos_at) {
            return [
                'isCounterOpened' => true,
                'isCounterClosed' => false,
            ];
        }

        return [
            'isCounterOpened' => true,
            'isCounterClosed' => true,
        ];
    }

    private function checkRequestDetails(Counter $counter, Cashier $cashier, OpenCounterData $openCounterData): void
    {
        $cashierQueries = resolve(CashierQueries::class);
        $locationIds = $cashierQueries->getCashierLocationsId($cashier);

        if (! in_array($counter->getLocationId(), $locationIds, true)) {
            abort(412, 'The cashier does not have access to the selected store.');
        }

        if ($counter->getIsLocked()) {
            abort(412, 'The selected counter is locked.');
        }

        if ($counter->getCounterUpdateId()) {
            abort(412, 'The selected counter has already been opened..');
        }

        if ($cashier->getCounterUpdateId()) {
            abort(412, 'The cashier has already opened a counter.');
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        /** @var ?CounterUpdate $lastClosedCounter */
        $lastClosedCounter = $counterUpdateQueries->getLastClosedTimeOfCounter($counter->id);

        if (! $lastClosedCounter instanceof CounterUpdate) { // if newly created counter open.
            return;
        }

        /** @var string $requestedOpenedAt */
        $requestedOpenedAt = $openCounterData->opened_by_pos_at ?? now()->format('Y-m-d H:i:s');

        /** @var Carbon $currentCounterOpenAtFormat */
        $currentCounterOpenAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $requestedOpenedAt);
        $currentCounterOpenAt = $currentCounterOpenAtFormat->format('Y-m-d H:i:s');

        /** @var string $lastClosedAt */
        $lastClosedAt = $lastClosedCounter->closed_by_pos_at ?? $lastClosedCounter->closed_at;

        /** @var Carbon $previousCounterClosedAtPosCreateFromFormat */
        $previousCounterClosedAtPosCreateFromFormat = Carbon::createFromFormat('Y-m-d H:i:s', $lastClosedAt);
        $previousCounterClosedAt = $previousCounterClosedAtPosCreateFromFormat->format('Y-m-d H:i:s');

        if ($currentCounterOpenAt <= $previousCounterClosedAt) {
            abort(412, 'Time travel is not possible! Opening a counter with a previous date is prohibited.');
        }
    }
}
