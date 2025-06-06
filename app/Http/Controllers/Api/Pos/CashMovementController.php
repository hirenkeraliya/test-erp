<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\DataObjects\PaginatedCashMovementsDataForPos;
use App\Domains\CashMovement\DataObjects\PosCashMovementData;
use App\Domains\CashMovement\Resources\PosCashMovementResource;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Director\DirectorQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\PosMismatch\Services\PosMismatchService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashMovementController extends Controller
{
    /**
     * @return array<string, mixed[]>
     */
    public function getAuthorizerTypes(): array
    {
        return [
            'cash_movement_authorizer_types' => AuthorizerTypes::getList(),
        ];
    }

    public function store(PosCashMovementData $posCashMovementData, Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $locationId = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId())->getKey();
        $mismatches = collect([]);

        $this->checkRequestDetails($posCashMovementData, $locationId, $companyId, $mismatches);

        DB::beginTransaction();

        try {
            $cashMovementQueries = resolve(CashMovementQueries::class);
            $cashMovement = $cashMovementQueries->addNew($posCashMovementData, $cashier->getCounterUpdateId());

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::CASH_MOVEMENT->value,
                $cashMovement->id,
                ModelMapping::CASH_MOVEMENT->name,
                $posCashMovementData->store_manager_authorization_code
            );

            $posMismatchQueries = resolve(PosMismatchQueries::class);

            foreach ($mismatches as $mismatch) {
                $posMismatchQueries->addNew($cashMovement, $mismatch);
            }

            DB::commit();

            $cashMovement = $cashMovementQueries->loadRelations($cashMovement);

            if ($cashMovement->mismatches->isNotEmpty()) {
                $messages = $cashMovement->mismatches->pluck('message')->toArray();

                $posMismatchService = resolve(PosMismatchService::class);
                $posMismatchService->logMismatchEntries(
                    'Add a New Cash Movement Mismatches',
                    $cashMovement->id,
                    $messages,
                    $cashMovement->offline_id
                );
            }

            return [
                'cash_movement' => new PosCashMovementResource($cashMovement),
            ];
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('POS Cash Movement', [
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

    public function checkRequestDetails(
        PosCashMovementData $posCashMovementData,
        int $locationId,
        int $companyId,
        Collection $mismatches
    ): void {
        if ($posCashMovementData->cash_movement_reason_id) {
            $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
            $cashMovementReason = $cashMovementReasonQueries->getById(
                $posCashMovementData->cash_movement_reason_id,
                $companyId
            );

            if ($cashMovementReason->type_id !== $posCashMovementData->cash_movement_type_id) {
                $mismatches->push(
                    'The selected cash movement type does not have an available cash movement reason.'
                );
            }
        }

        if ($posCashMovementData->authorizer_type === AuthorizerTypes::STORE_MANAGER->value) {
            $storeManagerQueries = resolve(StoreManagerQueries::class);
            if (! $storeManagerQueries->existsByIdStoreIdAndStatus($posCashMovementData->authorizer_id, $locationId)) {
                $mismatches->push('The selected store manager does not belong to the current counter`s location.');
            }

            $this->checkStoreManagerAuthorizationCode($posCashMovementData, $mismatches);
        }

        if ($posCashMovementData->authorizer_type === AuthorizerTypes::DIRECTOR->value) {
            $directorQueries = resolve(DirectorQueries::class);
            if (! $directorQueries->existsByIdLocationIdAndStatus(
                $posCashMovementData->authorizer_id,
                $companyId,
                $locationId
            )) {
                $mismatches->push('The selected director does not belong to the current counter`s location.');
            }
        }
    }

    public function checkStoreManagerAuthorizationCode(
        PosCashMovementData $posCashMovementData,
        Collection $mismatches
    ): void {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $mismatches,
            $posCashMovementData->authorizer_id,
            $posCashMovementData->store_manager_authorization_code,
            $posCashMovementData->happened_at
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaginatedCashMovements(
        Request $request,
        PaginatedCashMovementsDataForPos $paginatedCashMovementsDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $filterData = [
            'only_current_counter' => $paginatedCashMovementsDataForPos->only_current_counter,
            'from_date' => $paginatedCashMovementsDataForPos->from_date,
            'to_date' => $paginatedCashMovementsDataForPos->to_date,
            'per_page' => $paginatedCashMovementsDataForPos->per_page,
            'movement_type_id' => $paginatedCashMovementsDataForPos->movement_type_id,
            'sort_by' => $paginatedCashMovementsDataForPos->sort_by,
            'sort_direction' => $paginatedCashMovementsDataForPos->sort_direction,
            'search_text' => $paginatedCashMovementsDataForPos->search_text,
            'after_updated_at' => $paginatedCashMovementsDataForPos->after_updated_at,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $cashMovementQueries = resolve(CashMovementQueries::class);
        $cashMovements = $cashMovementQueries->getPaginatedCashMovements(
            $filterData,
            $location->id,
            $cashier->getCounterUpdateId()
        );

        return [
            'cash_movements' => PosCashMovementResource::collection($cashMovements),
            'total_records' => $cashMovements->total(),
            'last_page' => $cashMovements->lastPage(),
            'current_page' => $cashMovements->currentPage(),
            'per_page' => $cashMovements->perPage(),
        ];
    }

    /**
     * @return array<string, PosCashMovementResource>
     */
    public function getCashMovementDetails(Request $request, int|string $cashMovementId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $locationQueries = resolve(LocationQueries::class);
        $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $cashMovementQueries = resolve(CashMovementQueries::class);
        $cashMovement = $cashMovementQueries->getCashMovementByIdWithRelation($companyId, $cashMovementId);

        return [
            'cash_movement' => new PosCashMovementResource($cashMovement),
        ];
    }
}
