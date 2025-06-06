<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\ReGenerateTarget;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Services\SaleTargetSaleAmountService;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\SaleTarget;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleTargetQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $selectedColumns = [
            'id',
            'company_id',
            'name',
            'amount',
            'percentage',
            'amount_type',
            'target_type',
            'time_interval_type',
            'status',
            're_generate_target',
        ];

        return $this->saleTargetQuery($filterData, $companyId, $selectedColumns)->paginate($filterData['per_page']);
    }

    public function addNew(SaleTargetData $saleTargetData, int $companyId): SaleTarget
    {
        $saleTargetRecord['company_id'] = $companyId;
        $saleTargetRecord['name'] = $saleTargetData->name;
        $saleTargetRecord['amount'] = $this->saleTargetSalesAmount($saleTargetData, $companyId);
        $saleTargetRecord['percentage'] = $saleTargetData->percentage ?? null;
        $saleTargetRecord['amount_type'] = $saleTargetData->amount_type;
        $saleTargetRecord['target_type'] = $saleTargetData->target_type;
        $saleTargetRecord['time_interval_type'] = $saleTargetData->time_interval_type;
        $saleTargetRecord['status'] = Statuses::ACTIVE->value;
        $saleTargetRecord['re_generate_target'] = ReGenerateTarget::IN_PROGRESS->value;

        $saleTarget = SaleTarget::create($saleTargetRecord);

        $this->syncLocationsAndPromoters($saleTargetData, $saleTarget);

        return $saleTarget;
    }

    public function getById(int $saleTargetId, int $companyId): SaleTarget
    {
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        return SaleTarget::select(
            'id',
            'company_id',
            'name',
            'amount',
            'percentage',
            'amount_type',
            'target_type',
            'time_interval_type',
            'status'
        )
            ->with([
                'locations:' . $locationQueries->getNameColumnName(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleTargetTimeframes:' . $saleTargetTimeframeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($saleTargetId);
    }

    public function getByIds(array $saleTargetIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);

        return SaleTarget::select('id', 'company_id', 'name', 'amount', 'target_type', 'time_interval_type', 'status')
            ->with([
                'locations:' . $locationQueries->getNameColumnName(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleTargetTimeframes:' . $saleTargetTimeframeQueries->getBasicColumnNames(),
                'saleTargetTimeframes.saleAchievedTargets:' . $saleAchievedTargetQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleTargetIds);
    }

    public function update(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        $saleTarget->name = $saleTargetData->name;
        $saleTarget->amount = $this->saleTargetSalesAmount($saleTargetData, $saleTarget->company_id);
        $saleTarget->target_type = $saleTargetData->target_type;
        $saleTarget->percentage = $saleTargetData->percentage;
        $saleTarget->amount_type = $saleTargetData->amount_type;
        $saleTarget->time_interval_type = $saleTargetData->time_interval_type;
        $saleTarget->save();

        $this->syncLocationsAndPromoters($saleTargetData, $saleTarget);
    }

    public function getSaleTargetExport(array $filterData, int $companyId): Collection
    {
        $selectedColumns = [
            'id',
            'company_id',
            'name',
            'amount',
            'percentage',
            'amount_type',
            'target_type',
            'time_interval_type',
            'status',
        ];

        return $this->saleTargetQuery($filterData, $companyId, $selectedColumns)->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function filterByStatus(): Closure
    {
        return fn ($query) => $query->where('status', Statuses::ACTIVE->value);
    }

    public function filterByIdAndStatus(int $saleTargetId): Closure
    {
        return fn ($query) => $query->where('id', $saleTargetId)->where('status', Statuses::ACTIVE->value);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,company_id,name,amount,percentage,amount_type,target_type,time_interval_type,status';
    }

    public function adminSetStatus(int $saleTargetId, int $companyId, bool $status): void
    {
        $saleTarget = SaleTarget::query()
            ->where('company_id', $companyId)
            ->findOrFail($saleTargetId);
        $saleTarget->status = $status;
        $saleTarget->save();
    }

    public function markAsRegenerateStart(int $saleTargetId, int $companyId): void
    {
        $saleTarget = SaleTarget::query()
            ->where('company_id', $companyId)
            ->findOrFail($saleTargetId);

        $saleTarget->re_generate_target = ReGenerateTarget::IN_PROGRESS->value;
        $saleTarget->save();
    }

    public function markAsRegenerateCompete(int $saleTargetId): void
    {
        $saleTarget = SaleTarget::query()
            ->findOrFail($saleTargetId);

        $saleTarget->re_generate_target = ReGenerateTarget::COMPLETE->value;
        $saleTarget->save();
    }

    private function saleTargetSalesAmount(SaleTargetData $saleTargetData, int $companyId): float
    {
        $saleTargetSaleAmountService = resolve(SaleTargetSaleAmountService::class);

        return $saleTargetSaleAmountService->handleSaleTargetData($saleTargetData, $companyId);
    }

    public function getPaginatedListForPromoterApp(array $filterData, int $companyId): LengthAwarePaginator
    {
        $promoterQueries = resolve(PromoterQueries::class);

        $selectedColumns = ['id', 'name'];

        return $this->saleTargetQuery($filterData, $companyId, $selectedColumns)->whereHas(
            'promoters',
            $promoterQueries->filterById((int) $filterData['promoter_id'])
        )->paginate($filterData['per_page']);
    }

    public function getByIdForPromoterApp(int $promoterId, int $saleTargetId, int $companyId): Model
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        return $this->commonPrepareQueryForActiveStatusByCompany($companyId)
            ->with([
                'promoters' => $promoterQueries->filterById($promoterId),
                'promoters.targetable' => $saleAchievedTargetQueries->getColumnsFilterBySaleTargetId($saleTargetId),
                'promoters.targetable.saleTargetTimeframe:' . $saleTargetTimeframeQueries->getBasicColumnNames(),
            ])
            ->where('target_type', TargetType::PROMOTER_WISE->value)
            ->findOrFail($saleTargetId);
    }

    public function getPaginatedListForStoreManager(array $filterData, int $companyId): LengthAwarePaginator
    {
        $locationQueries = resolve(LocationQueries::class);

        $selectedColumns = ['id', 'name'];

        return $this->saleTargetQuery($filterData, $companyId, $selectedColumns)
            ->whereHas(
                'locations',
                $locationQueries->filterById((int) $filterData['location_id'], LocationTypes::STORE->value)
            )
            ->paginate($filterData['per_page']);
    }

    public function getByIdForStoreManagerApp(int $locationId, int $salesTargetId, int $companyId): Model
    {
        $locationQueries = resolve(LocationQueries::class);

        return $this->commonPrepareQueryForActiveStatusByCompany($companyId)
            ->where('target_type', TargetType::STORE_WISE->value)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->findOrFail($salesTargetId);
    }

    public function getPaginatedListByPromoter(array $filterData, int $companyId): LengthAwarePaginator
    {
        $locationQueries = resolve(LocationQueries::class);

        $selectedColumns = ['id', 'name'];

        return $this->saleTargetQuery($filterData, $companyId, $selectedColumns)
            ->whereHas('promoters', function ($query) use ($locationQueries, $filterData): void {
                $query->select('id')->whereHas(
                    'locations',
                    $locationQueries->filterById((int) $filterData['location_id'], LocationTypes::STORE->value)
                );
            })
            ->paginate($filterData['per_page']);
    }

    public function getIdByPromoter(int $locationId, int $salesTargetId, int $companyId): SaleTarget
    {
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        /** @var SaleTarget $salesTarget */
        $salesTarget = $this->commonPrepareQueryForActiveStatusByCompany($companyId)
            ->with([
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'promoters.targetable' => $saleAchievedTargetQueries->getColumnsFilterBySaleTargetId($salesTargetId),
                'promoters.targetable.saleTargetTimeframe:' . $saleTargetTimeframeQueries->getBasicColumnNames(),
            ])
            ->where('target_type', TargetType::PROMOTER_WISE->value)
            ->whereHas('promoters', function ($query) use ($locationQueries, $locationId): void {
                $query->select('id')->whereHas(
                    'locations',
                    $locationQueries->filterById($locationId, LocationTypes::STORE->value)
                );
            })
            ->findOrFail($salesTargetId);

        return $salesTarget;
    }

    private function saleTargetQuery(array $filterData, int $companyId, array $selectedColumns): Builder
    {
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        return SaleTarget::query()
            ->select(...$selectedColumns)
            ->with([
                'saleTargetTimeframes' => function ($query) use ($saleTargetTimeframeQueries): void {
                    $columns = explode(',', $saleTargetTimeframeQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->withCount('saleAchievedTargets');
                },
            ])
            ->when(isset($filterData['location_ids']), function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereHas('locations', function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                    })->orWhereHas('promoters', function ($query) use ($filterData): void {
                        $query->select('id')
                            ->whereHas('locations', function ($query) use ($filterData): void {
                                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                            });
                    });
                });
            })
            ->when(isset($filterData['promoter_ids']), function ($query) use ($filterData): void {
                $query->whereHas('promoters', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('promoter_id', $filterData['promoter_ids']);
                });
            })
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['target_type'], function ($query) use ($filterData): void {
                $query->where('target_type', $filterData['target_type']);
            })
            ->when($filterData['time_interval_type'], function ($query) use ($filterData): void {
                $query->where('time_interval_type', $filterData['time_interval_type']);
            })
            ->when(null !== $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function syncLocationsAndPromoters(SaleTargetData $saleTargetData, SaleTarget $saleTarget): void
    {
        $saleTarget->locations()->detach();
        $saleTarget->promoters()->detach();

        $saleTarget->locations()->attach($saleTargetData->location_ids);
        $saleTarget->promoters()->attach($saleTargetData->promoter_ids);
    }

    public function getSaleTargetWithAchieved(int $companyId): Collection
    {
        $saleTargetTimeframesQueries = resolve(SaleTargetTimeframeQueries::class);
        $saleAchievedTargetsQueries = resolve(SaleAchievedTargetQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return SaleTarget::query()
            ->select('id', 'name', 'amount', 'target_type', 'time_interval_type', 'status')
            ->with([
                'saleTargetTimeframes:' . $saleTargetTimeframesQueries->getBasicColumnNames(),
                'saleTargetTimeframes.saleAchievedTargets:' . $saleAchievedTargetsQueries->getBasicColumnNames(),
                'saleTargetTimeframes.saleAchievedTargets.targetable',
                'locations:' . $locationQueries->getNameColumnName(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('status', true)
            ->orderBy('target_type', 'asc')
            ->get();
    }

    private function commonPrepareQueryForActiveStatusByCompany(int $companyId): Builder
    {
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);

        return SaleTarget::query()
            ->select('id', 'name', 'amount', 'target_type', 'time_interval_type', 'status')
            ->with(['saleTargetTimeframes:' . $saleTargetTimeframeQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value);
    }

    public function getCurrentYearSalesTarget(int $year, int $companyId, ?int $filterId): Collection
    {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_timeframes.target_label as month',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::YEARLY->value)
            ->whereYear('current_timeframes.start_date', $year)
            ->whereYear('current_timeframes.end_date', $year)
            ->when(null !== $filterId, function ($query) use ($filterId): void {
                $query->where('current_targets.id', $filterId);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getYearSalesTarget(array $locationIds, array $promoterIds, int $year, int $companyId): Collection
    {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_timeframes.target_label as month',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::YEARLY->value)
            ->whereYear('current_timeframes.start_date', $year)
            ->whereYear('current_timeframes.end_date', $year)
            ->when([] !== $locationIds, function ($query) use ($locationIds): void {
                $query->where('current_achieved.targetable_type', 'LOCATION')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $locationIds);
            })
            ->when([] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->where('current_achieved.targetable_type', 'PROMOTER')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $promoterIds);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getCurrentMonthSalesTarget(int $year, int $companyId, ?int $filterId): Collection
    {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_timeframes.target_label as month',
                'current_timeframes.id as sale_target_timeframe_id',
                DB::raw('MONTH(current_timeframes.start_date) as month_date'),
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type'
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::MONTHLY->value)
            ->whereYear('current_timeframes.start_date', $year)
            ->whereYear('current_timeframes.end_date', $year)
            ->when(null !== $filterId, function ($query) use ($filterId): void {
                $query->where('current_targets.id', $filterId);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getMonthSalesTarget(
        array $timeframesIds,
        array $locationIds,
        array $promoterIds,
        int $companyId
    ): Collection {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_timeframes.target_label as month',
                'current_timeframes.id as sale_target_timeframe_id',
                DB::raw('MONTH(current_timeframes.start_date) as month_date'),
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type'
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::MONTHLY->value)
            ->whereIntegerInRaw('current_timeframes.id', $timeframesIds)
            ->when([] !== $locationIds, function ($query) use ($locationIds): void {
                $query->where('current_achieved.targetable_type', 'LOCATION')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $locationIds);
            })
            ->when([] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->where('current_achieved.targetable_type', 'PROMOTER')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $promoterIds);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getCurrentWeekSalesTarget(int $year, int $companyId, ?int $filterId): Collection
    {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                DB::raw('MONTH(current_timeframes.start_date) as month_date'),
                'current_timeframes.id as sale_target_timeframe_id',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
                DB::raw('WEEK(current_timeframes.start_date, 3) as week_number'),
                DB::raw('CONCAT("Week ", WEEK(current_timeframes.start_date, 3)) as week_name')
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::WEEKLY->value)
            ->whereYear('current_timeframes.start_date', $year)
            ->whereYear('current_timeframes.end_date', $year)
            ->when(null !== $filterId, function ($query) use ($filterId): void {
                $query->where('current_targets.id', $filterId);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getWeekSalesTarget(
        array $timeframesIds,
        array $locationIds,
        array $promoterIds,
        int $companyId
    ): Collection {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                DB::raw('MONTH(current_timeframes.start_date) as month_date'),
                'current_timeframes.id as sale_target_timeframe_id',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
                DB::raw('WEEK(current_timeframes.start_date, 3) as week_number'),
                DB::raw('CONCAT("Week ", WEEK(current_timeframes.start_date, 3)) as week_name')
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::WEEKLY->value)
            ->whereIntegerInRaw('current_timeframes.id', $timeframesIds)
            ->when([] !== $locationIds, function ($query) use ($locationIds): void {
                $query->where('current_achieved.targetable_type', 'LOCATION')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $locationIds);
            })
            ->when([] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->where('current_achieved.targetable_type', 'PROMOTER')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $promoterIds);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getCurrentDailySalesTarget(int $year, int $companyId, ?int $filterId): Collection
    {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                DB::raw('DATE(current_timeframes.start_date) as date'),
                'current_timeframes.id as sale_target_timeframe_id',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::DAILY->value)
            ->whereYear('current_timeframes.start_date', $year)
            ->whereYear('current_timeframes.end_date', $year)
            ->when(null !== $filterId, function ($query) use ($filterId): void {
                $query->where('current_targets.id', $filterId);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getDailySalesTarget(
        array $timeframesIds,
        array $locationIds,
        array $promoterIds,
        int $companyId
    ): Collection {
        return DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                DB::raw('DATE(current_timeframes.start_date) as date'),
                'current_timeframes.id as sale_target_timeframe_id',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::DAILY->value)
            ->whereIntegerInRaw('current_timeframes.id', $timeframesIds)
            ->when([] !== $locationIds, function ($query) use ($locationIds): void {
                $query->where('current_achieved.targetable_type', 'LOCATION')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $locationIds);
            })
            ->when([] !== $promoterIds, function ($query) use ($promoterIds): void {
                $query->where('current_achieved.targetable_type', 'PROMOTER')
                    ->whereIntegerInRaw('current_achieved.targetable_id', $promoterIds);
            })
            ->get()
            ->groupBy(['target_type']);
    }

    public function getPreviousMonthSalesTarget(array $existingMonths, int $companyId): Collection
    {
        $query = DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_timeframes.target_label as month1',
                DB::raw('MONTH(current_timeframes.start_date) as month'),
                DB::raw('YEAR(current_timeframes.start_date) as year'),
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type'
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::MONTHLY->value);

        $query->where(function ($subquery) use ($existingMonths): void {
            foreach ($existingMonths as $monthData) {
                $subquery->orWhere(function ($orSubquery) use ($monthData): void {
                    $orSubquery->whereMonth('current_timeframes.start_date', $monthData['previous_month'])
                        ->whereYear('current_timeframes.start_date', $monthData['previous_year'])
                        ->where('current_targets.target_type', $monthData['target_type']);
                });
            }
        });

        return $query->get()->groupBy(['target_type']);
    }

    public function getPreviousWeekSalesTarget(array $existingWeeks, int $companyId): Collection
    {
        $query = DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
                DB::raw('WEEK(current_timeframes.start_date, 3) as week_number'),
                DB::raw('CONCAT("Week ", WEEK(current_timeframes.start_date, 3)) as week_name')
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::WEEKLY->value);

        $query->where(function ($subquery) use ($existingWeeks): void {
            foreach ($existingWeeks as $weekData) {
                $subquery->orWhere(function ($orSubquery) use ($weekData): void {
                    $orSubquery->where('current_timeframes.start_date', $weekData['start_of_week'])
                        ->where('current_timeframes.end_date', $weekData['end_of_week'])
                        ->where('current_targets.target_type', $weekData['target_type']);
                });
            }
        });

        return $query->get()->groupBy(['target_type']);
    }

    public function getPreviousDailySalesTarget(array $existingDailies, int $companyId): Collection
    {
        $query = DB::table('sale_targets as current_targets')
            ->select(
                'current_targets.id as sale_target_id',
                'current_targets.name as sale_target_name',
                'current_achieved.target_value as target_value',
                'current_achieved.achieved_value as achieved_value',
                'current_achieved.targetable_type as targetable_type',
                'current_targets.target_type as target_type',
                DB::raw('DATE(current_timeframes.start_date) as date'),
            )
            ->join(
                'sale_target_timeframes as current_timeframes',
                'current_targets.id',
                '=',
                'current_timeframes.sale_target_id'
            )
            ->join(
                'sale_achieved_targets as current_achieved',
                'current_timeframes.id',
                '=',
                'current_achieved.sale_target_timeframe_id'
            )
            ->where('current_targets.company_id', $companyId)
            ->where('current_targets.status', Statuses::ACTIVE->value)
            ->where('current_targets.time_interval_type', TimeIntervalType::DAILY->value);

        $query->where(function ($subquery) use ($existingDailies): void {
            foreach ($existingDailies as $existingDaily) {
                $subquery->orWhere(function ($orSubquery) use ($existingDaily): void {
                    $orSubquery->where('current_timeframes.start_date', $existingDaily['start_of_day'])
                        ->where('current_timeframes.end_date', $existingDaily['end_of_day'])
                        ->where('current_targets.target_type', $existingDaily['target_type']);
                });
            }
        });

        return $query->get()->groupBy(['target_type']);
    }

    public function getLocations(array $saleTargetIds, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return SaleTarget::query()
            ->select('id')
            ->with(['locations:' . $locationQueries->getNameColumnName()])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $saleTargetIds)
            ->get();
    }

    public function getPromoters(array $saleTargetIds, int $companyId): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);

        return SaleTarget::query()
            ->select('id')
            ->with(['promoters:' . $promoterQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $saleTargetIds)
            ->get();
    }

    public function getListForSaleTargetChart(): array
    {
        $selectedColumns = ['id', 'name'];

        return SaleTarget::query()->select($selectedColumns)->get()->toArray();
    }

    public function getSaleTargetForChart(
        array $dateRange,
        int $companyId,
        int $targetType,
        int $intervalType
    ): Collection {
        return SaleTarget::query()
            ->select('sale_targets.id', 'sale_targets.amount', 'sale_target_timeframes.target_label')
            ->join('sale_target_timeframes', 'sale_target_timeframes.sale_target_id', 'sale_targets.id')
            ->where('company_id', $companyId)
            ->whereBetween(
                'sale_target_timeframes.start_date',
                [date('Y-m-d', strtotime($dateRange[0])), date('Y-m-d', strtotime($dateRange[1]))]
            )
            ->where('target_type', $targetType)
            ->where('time_interval_type', $intervalType)
            ->groupBy('sale_target_timeframes.target_label')
            ->get();
    }
}
