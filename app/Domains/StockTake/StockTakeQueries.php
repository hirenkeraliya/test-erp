<?php

declare(strict_types=1);

namespace App\Domains\StockTake;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Models\StockTake;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockTakeQueries
{
    public function listQuery(array $filterData, int $locationId, int $companyId): LengthAwarePaginator
    {
        return $this->storeAndWarehouseManagerStockTakeQuery(
            $filterData,
            $locationId,
            $companyId
        )->paginate($filterData['per_page']);
    }

    public function addNew(array $records): StockTake
    {
        return StockTake::create($records);
    }

    public function submit(
        int $stockTakeId,
        int $managerId,
        int $locationId,
        string $managerType,
        string $compareStockDate,
        int $companyId
    ): void {
        $stockTake = StockTake::query()
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->findOrFail($stockTakeId);
        $stockTake->compare_stock_date = $compareStockDate;
        $stockTake->submitted_by_id = $managerId;
        $stockTake->submitted_by_type = $managerType;
        $stockTake->submitted_at = now()->toDateTimeString();
        $stockTake->save();
    }

    public function filterSubmittedById(int $stockTakeId): Closure
    {
        return fn ($query) => $query->select('id')->where('id', $stockTakeId)
            ->whereNotNull('submitted_at')
            ->whereNotNull('submitted_by_id');
    }

    public function getAdminListQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->stockTakeQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function fetchSubmittedOnly(int $locationId, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('location_id', $locationId)
            ->where('company_id', $companyId)
            ->whereNotNull('submitted_by_id')
            ->whereNotNull('submitted_at');
    }

    public function anyPendingStockTakeByManager(int $locationId, int $companyId): bool
    {
        return StockTake::where('location_id', $locationId)
            ->where('company_id', $companyId)
            ->whereNull('submitted_by_id')
            ->whereNull('submitted_at')
            ->exists();
    }

    public function isStockTakePending(int $stockTakeId): bool
    {
        return StockTake::where('id', $stockTakeId)
            ->whereNotNull('submitted_by_id')
            ->whereNotNull('submitted_at')
            ->exists();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function getBasicColumnNamesForManager(): string
    {
        return 'id,employee_id';
    }

    public function searchLocationByName(string $searchText): Closure
    {
        return fn ($query) => $query->where('name', 'like', '%' . $searchText . '%');
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getById(int $stockTakeId): StockTake
    {
        return StockTake::select('id', 'stock_record_date', 'requested_by_id', 'submitted_by_id')
            ->findOrFail($stockTakeId);
    }

    public function updateStockTakeStatus(int $stockTakeId): void
    {
        $stockTake = StockTake::query()
            ->findOrFail($stockTakeId);
        $stockTake->is_uploaded_products = true;
        $stockTake->save();
    }

    public function getStockTakesExport(array $filterData, int $companyId): Collection
    {
        return $this->stockTakeQuery($filterData, $companyId)->get();
    }

    public function getStoreAndWarehouseMangerStockTakesExport(
        array $filterData,
        int $locationId,
        int $companyId
    ): Collection {
        return $this->storeAndWarehouseManagerStockTakeQuery($filterData, $locationId, $companyId)->get();
    }

    public function filterByLocationIdAndCompanyId(int $locationId, int $companyId): Closure
    {
        return fn ($query) => $query->select(
            'id'
        )->where('location_id', $locationId)->where('company_id', $companyId);
    }

    public function setUpdatedAt(int $stockTakeId): void
    {
        $stockTake = StockTake::query()
            ->select('id')
            ->findOrFail($stockTakeId);

        $stockTake->touch();
    }

    public function getLocationColumnsByIdAndCompanyId(int $id, int $companyId): StockTake
    {
        return StockTake::query()
            ->select('id', 'location_id')
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    private function storeAndWarehouseManagerStockTakeQuery(
        array $filterData,
        int $locationId,
        int $companyId
    ): Builder {
        $employeeQueries = resolve(EmployeeQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        return StockTake::query()
            ->select(
                'id',
                'stock_record_date',
                'company_id',
                'compare_stock_date',
                'requested_by_id',
                'requested_by_type',
                'location_id',
                'submitted_by_id',
                'submitted_by_type',
                'submitted_at',
                'compare_stock_date',
                'is_uploaded_products'
            )
            ->with([
                'location:' . $this->getBasicColumnNames(),
                'requestedBy:' . $this->getBasicColumnNamesForManager(),
                'requestedBy.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'submittedBy:' . $this->getBasicColumnNamesForManager(),
                'submittedBy.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'importRecord:' . $importRecordQueries->getBasicColumns(),
            ])
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->orWhereHas('location', $this->searchLocationByName($filterData['search_text']))
                    ->orWhereHasMorph(
                        'requestedBy',
                        [ModelMapping::WAREHOUSE_MANAGER->name, ModelMapping::STORE_MANAGER->name],
                        $this->searchByEmployeeBasicColumns($filterData['search_text'])
                    )
                    ->orWhereHasMorph(
                        'submittedBy',
                        [ModelMapping::WAREHOUSE_MANAGER->name, ModelMapping::STORE_MANAGER->name],
                        $this->searchByEmployeeBasicColumns($filterData['search_text'])
                    );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function stockTakeQuery(array $filterData, int $companyId): Builder
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StockTake::query()
            ->select(
                'id',
                'requested_by_id',
                'requested_by_type',
                'location_id',
                'submitted_by_id',
                'submitted_by_type',
                'stock_record_date',
                'submitted_at',
                'compare_stock_date'
            )
            ->where('company_id', $companyId)
            ->whereNotNull('submitted_at')
            ->whereNotNull('submitted_by_id')
            ->with([
                'location:' . $this->getBasicColumnNames(),
                'requestedBy:' . $this->getBasicColumnNamesForManager(),
                'requestedBy.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'submittedBy:' . $this->getBasicColumnNamesForManager(),
                'submittedBy.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->orWhereHas('location', $this->searchLocationByName($filterData['search_text']))
                    ->orWhereHasMorph(
                        'requestedBy',
                        [ModelMapping::WAREHOUSE_MANAGER->name, ModelMapping::STORE_MANAGER->name],
                        $this->searchByEmployeeBasicColumns($filterData['search_text'])
                    )
                    ->orWhereHasMorph(
                        'submittedBy',
                        [ModelMapping::WAREHOUSE_MANAGER->name, ModelMapping::STORE_MANAGER->name],
                        $this->searchByEmployeeBasicColumns($filterData['search_text'])
                    );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function searchByEmployeeBasicColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->whereHas(
            'employee',
            function ($query) use ($searchText): void {
                $query
                    ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $searchText . '%');
            }
        );
    }
}
