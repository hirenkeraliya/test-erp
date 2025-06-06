<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Resources\ClosedCounterUpdateResource;
use App\Domains\CounterUpdate\Resources\DayCloseCounterUpdateListResource;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\CounterUpdate\Services\CounterUpdateDeclarationAttemptService;
use App\Domains\Denomination\DenominationQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\StoreDayClose\Exports\StoreDayClosePageExport;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Http\Controllers\Controller;
use App\Models\CounterUpdate;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DayCloseController extends Controller
{
    public function __construct(
        protected CounterUpdateQueries $counterUpdateQueries
    ) {
    }

    public function index(): Response
    {
        $counterQueries = resolve(CounterQueries::class);
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $totalCounters = $counterQueries->getCountByLocation($locationId);

        $lastStoreDayClose = $storeDayCloseQueries->getLastDayClose($locationId);

        $dayCloseCounters = $this->counterUpdateQueries->getByDayCloseAndStore(
            $locationId,
            $companyId,
            $lastStoreDayClose
        );

        return Inertia::render('day_close/Index', [
            'dayCloseCounters' => DayCloseCounterUpdateListResource::collection($dayCloseCounters),
            'hasMultipleCounters' => $totalCounters >= 1,
            'exportPermission' => PermissionList::getExportPermissionName('day_close'),
        ]);
    }

    /**
     * @return array<string, mixed[]>
     */
    public function counterClosingDetails(int $counterUpdateId): array
    {
        $counterUpdate = $this->counterUpdateQueries->getByIdFilterByStore(
            session('store_manager_selected_location_id'),
            $counterUpdateId
        );

        $companyId = session('store_manager_selected_location_company_id');

        $denominationQueries = resolve(DenominationQueries::class);
        $denominations = $denominationQueries->getByCompanyId($companyId);

        $denominations = $denominations->map(function ($denomination) {
            $denomination->quantity = 0;

            return $denomination;
        });

        return [
            'counter_closing_details' => [
                'mismatch_amount' => $counterUpdate->getMismatchAmount(),
                'amount_mismatch_reason' => $counterUpdate->getAmountMismatchReason(),
                'denominations' => $denominations->toArray(),
                ...$this->prepareCounterClosingDetails($counterUpdate),
            ],
        ];
    }

    public function closeCounter(
        CloseCounterDataForStoreManager $closeCounterData,
        int $counterUpdateId,
        Request $request
    ): RedirectResponse {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $counterUpdate = $this->counterUpdateQueries->getByIdFilterByStore(
            session('store_manager_selected_location_id'),
            $counterUpdateId
        );

        $closeCounterService = resolve(CloseCounterService::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $counter = $counterQueries->getByCounterUpdateId($counterUpdate->getKey());
        $cashier = $cashierQueries->getByCounterUpdateId($counterUpdate->getKey());

        $this->addCounterUpdateDeclarationAttempt($counterUpdate, $closeCounterData, $cashier->id);

        $counterClosingDetails = $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate);
        $closeCounterService->checkRequestDetails($closeCounterData, $counterClosingDetails, 417);

        DB::beginTransaction();

        try {
            $closeCounterService->closeCounter(
                $closeCounterData,
                $counterUpdate,
                $counterClosingDetails,
                ModelMapping::STORE_MANAGER->name,
                $storeManager->id,
            );

            $counterQueries->unsetCounterUpdateId($counter);

            $cashierQueries->unsetCounterUpdateId($cashier);

            DB::commit();

            return back()
                ->with('success', 'Counter Closed successfully.');
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

    public function addCounterUpdateDeclarationAttempt(
        CounterUpdate $counterUpdate,
        CloseCounterDataForStoreManager $closeCounterData,
        int $casherId,
    ): void {
        $counterUpdateDeclarationAttemptService = resolve(CounterUpdateDeclarationAttemptService::class);
        $payments = $counterUpdateDeclarationAttemptService->getDeclarationAttemptPayments(
            $counterUpdate,
            $closeCounterData
        );
        $counterUpdateDeclarationAttemptService->saveDeclarationAttemptDetails(
            $counterUpdate,
            $payments,
            $casherId,
            session('store_manager_selected_location_id')
        );
    }

    public function dayClose(Request $request): array
    {
        $locationId = session('store_manager_selected_location_id');
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $lastStoreDayClose = $storeDayCloseQueries->getLastDayClose($locationId);
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getByIdWithReceiptFooterDisclaimerAndCreatedAt($locationId);

        $totalOpenCounters = $this->counterUpdateQueries->getOpenCountersCountFilterByStoreAndDates(
            $locationId,
            $lastStoreDayClose
        );

        if ($totalOpenCounters > 0) {
            abort(
                412,
                $totalOpenCounters . ' counters are still open. Please close all the counters for Day Close first.'
            );
        }

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();
        $requestLock = Cache::lock('day_close_operation_working_' . $locationId);
        if ($requestLock->get()) {
            DB::beginTransaction();
            try {
                $storeDayCloseService = resolve(StoreDayCloseService::class);
                $storeDayClose = $storeDayCloseService->addStoreDayClose(
                    $this->counterUpdateQueries,
                    $storeDayCloseQueries,
                    $location,
                    $lastStoreDayClose,
                    $storeManager->getKey()
                );

                DB::commit();

                return [
                    'location_day_close' => $storeDayCloseQueries->loadRelations($storeDayClose),
                    'location_receipt_footer' => $location->receipt_footer,
                    'location_disclaimer' => $location->disclaimer,
                ];
            } catch (Throwable $throwable) {
                Log::error('StoreManager-Day-Close', [
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

    public function exportDayClose(string $filename): BinaryFileResponse
    {
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $lastStoreDayClose = $storeDayCloseQueries->getLastDayClose($locationId);

        $dayCloseCounters = $this->counterUpdateQueries->getByDayCloseAndStore(
            $locationId,
            $companyId,
            $lastStoreDayClose
        );

        return Excel::download(new StoreDayClosePageExport($dayCloseCounters), $filename);
    }

    /**
     * @return mixed[]
     */
    private function prepareCounterClosingDetails(CounterUpdate $counterUpdate): array
    {
        if (null === $counterUpdate->closed_at) {
            $closeCounterService = resolve(CloseCounterService::class);

            return array_merge([
                'closed_at' => 'N/A',
            ], $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate));
        }

        $counterUpdate = $this->counterUpdateQueries->getByIdWithRelationsFilterByStore(
            session('store_manager_selected_location_id'),
            $counterUpdate->id
        );

        /** @var Carbon|string $closedAt */
        $closedAt = 'N/A';

        if ($counterUpdate->closed_at) {
            /** @var Carbon $closedAtFormat */
            $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->closed_at);
            $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
        }

        $closeCounterDetails = new ClosedCounterUpdateResource($counterUpdate);
        $closeCounterDetails = json_decode($closeCounterDetails->toJson(), true, 512, JSON_THROW_ON_ERROR);

        return array_merge([
            'closed_at' => $closedAt,
            'denominations' => $counterUpdate->denominations->map(fn ($denomination): array => [
                'denomination' => $denomination->denomination,
                'denomination_quantity' => $denomination->quantity,
            ]),
        ], $closeCounterDetails);
    }
}
