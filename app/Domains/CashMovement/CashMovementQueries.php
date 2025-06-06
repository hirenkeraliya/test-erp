<?php

declare(strict_types=1);

namespace App\Domains\CashMovement;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\CashMovement\DataObjects\PosCashMovementData;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\CashMovementReason\Enums\StaticCashMovementReasons;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Models\CashMovement;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashMovementQueries
{
    public function addNew(PosCashMovementData $posCashMovementData, int $counterUpdateId): CashMovement
    {
        $data = $posCashMovementData->all();
        unset($data['store_manager_authorization_code']);
        $data['counter_update_id'] = $counterUpdateId;
        $data['authorizer_type'] = ModelMapping::getCaseName($data['authorizer_type']);

        return CashMovement::create($data);
    }

    public function addNewForCashback(
        string $saleOfflineId,
        int $counterUpdateId,
        float $cashbackAmount,
        string $happenedAt
    ): int {
        return CashMovement::create([
            'offline_id' => $saleOfflineId,
            'counter_update_id' => $counterUpdateId,
            'cash_movement_type_id' => CashMovementTypes::CASH_OUT->value,
            'cash_movement_reason_id' => StaticCashMovementReasons::CASHBACK->value,
            'amount' => $cashbackAmount,
            'happened_at' => $happenedAt,
        ])->id;
    }

    public function getByCounterUpdateId(int $counterUpdateId): Collection
    {
        return CashMovement::query()
            ->select('id', 'cash_movement_type_id', 'amount')
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }

    public function getPaginatedListByIdFilterByText(array $filterData, int $companyId): LengthAwarePaginator
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CashMovement::query()
            ->select(
                'id',
                'counter_update_id',
                'cash_movement_reason_id',
                'cash_movement_type_id',
                'other_reason',
                'remarks',
                'amount',
                'created_at',
                'authorizer_id',
                'authorizer_type',
                'happened_at'
            )
            ->with(
                'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'authorizer',
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashMovementReasonQueries,
                $counterUpdateQueries,
            ): void {
                $query->where(function ($query) use (
                    $filterData,
                    $cashMovementReasonQueries,
                    $counterUpdateQueries,
                ): void {
                    $query->whereHas(
                        'cashMovementReason',
                        $cashMovementReasonQueries->searchByReason($filterData['search_text'])
                    )->orWhereHas(
                        'counterUpdate',
                        $counterUpdateQueries->searchByCounterAndStoreName($filterData['search_text'])
                    )->orWhereHas(
                        'authorizer',
                        $this->searchByEmployeeFirstNameAndLastName($filterData['search_text'])
                    )->orWhereAny(
                        ['other_reason', 'amount'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )->orWhereIntegerInRaw(
                        'cash_movement_type_id',
                        CashMovementTypes::getMatchingCases($filterData['search_text'])
                    );
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($counterQueries, $filterData): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds($filterData['counter_ids'])
                );
            })
            ->when($filterData['cash_movement_type'], function ($query) use ($filterData): void {
                $query->where('cash_movement_type_id', (int) $filterData['cash_movement_type']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getPaginatedCashMovementListsForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->cashMovementListsForStoreManager($filterData, $companyId, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function getCashMovementListsForExport(array $filterData, int $companyId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CashMovement::query()
            ->select(
                'id',
                'counter_update_id',
                'cash_movement_reason_id',
                'cash_movement_type_id',
                'other_reason',
                'remarks',
                'amount',
                'created_at',
                'authorizer_id',
                'authorizer_type',
                'happened_at'
            )
            ->with(
                'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'authorizer',
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashMovementReasonQueries,
                $counterUpdateQueries,
            ): void {
                $query->where(function ($query) use (
                    $filterData,
                    $cashMovementReasonQueries,
                    $counterUpdateQueries,
                ): void {
                    $query->whereHas(
                        'cashMovementReason',
                        $cashMovementReasonQueries->searchByReason($filterData['search_text'])
                    )->orWhereHas(
                        'counterUpdate',
                        $counterUpdateQueries->searchByCounterAndStoreName($filterData['search_text'])
                    )->orWhereHas(
                        'authorizer',
                        $this->searchByEmployeeFirstNameAndLastName($filterData['search_text'])
                    )->orWhereIntegerInRaw(
                        'cash_movement_type_id',
                        CashMovementTypes::getMatchingCases($filterData['search_text'])
                    )->orWhereAny(['other_reason', 'amount'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($counterQueries, $filterData): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cash_movement_type'], function ($query) use ($filterData): void {
                $query->where('cash_movement_type_id', (int) $filterData['cash_movement_type']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function loadRelations(CashMovement $cashMovement): CashMovement
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return $cashMovement->load(
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
            'authorizer',
            'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
        );
    }

    public function getPaginatedCashMovements(
        array $filterData,
        int $locationId,
        int $counterUpdateId
    ): LengthAwarePaginator {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CashMovement::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'cash_movement_reason_id',
                'cash_movement_type_id',
                'other_reason',
                'remarks',
                'amount',
                'created_at',
                'happened_at',
                'authorizer_id',
                'authorizer_type',
            )
            ->with(
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
                'authorizer',
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->when($filterData['only_current_counter'], function ($query) use ($counterUpdateId): void {
                $query->where('counter_update_id', $counterUpdateId);
            }, function ($query) use ($locationId, $counterUpdateQueries): void {
                $query->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId));
            })->when(
                $filterData['movement_type_id'],
                function ($query) use ($filterData): void {
                    $query->where('cash_movement_type_id', (int) $filterData['movement_type_id']);
                }
            )
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashMovementReasonQueries,
                $counterUpdateQueries
            ): void {
                $query->where(function ($query) use (
                    $filterData,
                    $cashMovementReasonQueries,
                    $counterUpdateQueries,
                ): void {
                    $query->whereHas(
                        'cashMovementReason',
                        $cashMovementReasonQueries->searchByReason($filterData['search_text'])
                    )->orWhereHas(
                        'counterUpdate',
                        $counterUpdateQueries->searchByCounterAndStoreName($filterData['search_text'])
                    )->orWhereHas(
                        'authorizer',
                        $this->searchByEmployeeFirstNameAndLastName($filterData['search_text'])
                    )->orWhereAny(['other_reason', 'amount'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query) use ($filterData): void {
                $query->when($filterData['from_date'], function ($query) use ($filterData): void {
                    $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['from_date']));
                });
                $query->when($filterData['to_date'], function ($query) use ($filterData): void {
                    $query->where('created_at', '<=', CommonFunctions::addEndTime($filterData['to_date']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getCashMovementByIdWithRelation(int $companyId, int|string $cashMovementId): CashMovement
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CashMovement::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'cash_movement_reason_id',
                'cash_movement_type_id',
                'other_reason',
                'remarks',
                'amount',
                'created_at',
                'authorizer_id',
                'authorizer_type',
                'happened_at'
            )
            ->with(
                'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'authorizer',
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->where(function ($query) use ($cashMovementId): void {
                $query->where('offline_id', $cashMovementId)
                    ->orWhere('id', $cashMovementId);
            })
            ->firstOrFail();
    }

    public function getCashMovementListsForExportInStoreManagerPanel(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->cashMovementListsForStoreManager($filterData, $companyId, $locationId)->get();
    }

    public function addNewForCashbackReversal(
        string $saleOfflineId,
        int $counterUpdateId,
        float $cashbackAmount,
        string $happenedAt
    ): int {
        return CashMovement::create([
            'offline_id' => $saleOfflineId . '-void-sale',
            'counter_update_id' => $counterUpdateId,
            'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
            'cash_movement_reason_id' => StaticCashMovementReasons::CASHBACK_REVERSAL->value,
            'amount' => $cashbackAmount,
            'happened_at' => $happenedAt,
        ])->id;
    }

    public function getCashMovementForReport(array $filterData, int $companyId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CashMovement::query()
            ->select(
                'id',
                'counter_update_id',
                'cash_movement_type_id',
                'amount',
                'happened_at',
                'cash_movement_reason_id'
            )
            ->with(
                'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
            )
            ->whereHas('counterUpdate', function ($query) use (
                $filterData,
                $counterUpdateQueries,
                $companyId
            ): void {
                $query->where($counterUpdateQueries->filterByCompanyId($companyId))
                    ->when(null !== $filterData['counter_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                    })
                    ->when(null !== $filterData['cashier_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                    })
                    ->when(null !== $filterData['location_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where($counterUpdateQueries->filterByStoreIds($filterData['location_ids']));
                    });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->get();
    }

    private function searchByEmployeeFirstNameAndLastName(string $searchText): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select('id')
            ->whereHas('employee', $employeeQueries->searchByFirstAndLastName($searchText));
    }

    private function cashMovementListsForStoreManager(array $filterData, int $companyId, int $locationId): Builder
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CashMovement::query()
            ->select(
                'id',
                'counter_update_id',
                'cash_movement_reason_id',
                'cash_movement_type_id',
                'other_reason',
                'remarks',
                'amount',
                'created_at',
                'authorizer_id',
                'authorizer_type',
                'happened_at'
            )
            ->with(
                'cashMovementReason:' . $cashMovementReasonQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'authorizer',
                'authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashMovementReasonQueries,
                $counterUpdateQueries,
            ): void {
                $query->where(function ($query) use (
                    $filterData,
                    $cashMovementReasonQueries,
                    $counterUpdateQueries,
                ): void {
                    $query->whereHas(
                        'cashMovementReason',
                        $cashMovementReasonQueries->searchByReason($filterData['search_text'])
                    )->orWhereHas(
                        'counterUpdate',
                        $counterUpdateQueries->searchByCounterAndStoreName($filterData['search_text'])
                    )->orWhereHas(
                        'authorizer',
                        $this->searchByEmployeeFirstNameAndLastName($filterData['search_text'])
                    )->orWhereIntegerInRaw(
                        'cash_movement_type_id',
                        CashMovementTypes::getMatchingCases($filterData['search_text'])
                    )->orWhereAny(['other_reason', 'amount'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds($filterData['counter_ids'])
                );
            })
            ->when($filterData['cash_movement_type'], function ($query) use ($filterData): void {
                $query->where('cash_movement_type_id', (int) $filterData['cash_movement_type']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function filterByCounterUpdateId(int $counterUpdateId): Closure
    {
        return fn ($query) => $query->select('id')->where('counter_update_id', $counterUpdateId);
    }
}
