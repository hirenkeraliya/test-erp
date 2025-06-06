<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockAdjustment\DataObjects\StockAdjustmentData;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Models\StockAdjustment;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockAdjustmentQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->stockAdjustmentQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(StockAdjustmentData $stockAdjustmentData, int $companyId, User $user): StockAdjustment
    {
        return StockAdjustment::create([
            'company_id' => $companyId,
            'created_by_admin_id' => $user->id,
            'reason' => $stockAdjustmentData->reason,
            'approved_by_employee_id' => $stockAdjustmentData->approved_by_employee_id,
            'adjustment_date' => $stockAdjustmentData->adjustment_date,
            'type_id' => $stockAdjustmentData->type_id,
        ]);
    }

    public function storeManagerListQuery(array $filterData, int $companyId, int $locationId): LengthAwarePaginator
    {
        return $this->storeManagerStockAdjustmentQuery($filterData, $companyId, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function getByIdWithItems(int $stockAdjustmentId, int $companyId): StockAdjustment
    {
        $productQueries = new ProductQueries();
        $stockAdjustmentItemQueries = new StockAdjustmentItemQueries();

        return StockAdjustment::select('id', 'reason', 'created_at')
            ->where('company_id', $companyId)
            ->with(
                'items',
                'items.location:' . $stockAdjustmentItemQueries->getLocationBasicColumn(),
                'items.product:' . $productQueries->getBasicColumnNames()
            )
            ->findOrFail($stockAdjustmentId);
    }

    public function getById(int $stockAdjustmentId, int $companyId): StockAdjustment
    {
        return StockAdjustment::select(
            'id',
            'reason',
            'created_at',
            'company_id',
            'created_by_admin_id',
            'approved_by_employee_id',
            'adjustment_date',
            'type_id'
        )
            ->where('company_id', $companyId)
            ->findOrFail($stockAdjustmentId);
    }

    public function getByIdWithItemsForManagerPanel(
        int $stockAdjustmentId,
        int $companyId,
        int $locationId,
    ): StockAdjustment {
        $productQueries = new ProductQueries();
        $stockAdjustmentItemQueries = new StockAdjustmentItemQueries();

        return StockAdjustment::select('id', 'reason', 'created_at')
            ->where('company_id', $companyId)
            ->with([
                'items' => function ($query) use ($stockAdjustmentItemQueries, $locationId): void {
                    $query->where($stockAdjustmentItemQueries->getLocationItems($locationId));
                },
                'items.location:' . $stockAdjustmentItemQueries->getLocationBasicColumn(),
                'items.product:' . $productQueries->getBasicColumnNames(),
            ])
            ->whereHas('items', $stockAdjustmentItemQueries->getLocationItems($locationId))
            ->findOrFail($stockAdjustmentId);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function warehouseManagerListQuery(array $filterData, int $companyId, int $locationId): LengthAwarePaginator
    {
        return $this->warehouseManagerStockAdjustmentQuery($filterData, $companyId, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function getWarehouseManagerStockAdjustmentsExport(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->warehouseManagerStockAdjustmentQuery($filterData, $companyId, $locationId)->get();
    }

    public function warehouseManagerStockAdjustmentQuery(array $filterData, int $companyId, int $locationId): Builder
    {
        $employeeQueries = new EmployeeQueries();

        return StockAdjustment::query()
            ->select('id', 'reason', 'approved_by_employee_id', 'type_id', 'adjustment_date')
            ->with('employee:' . $employeeQueries->getFirstAndLastNameColumns())
            ->whereHas('items', function ($query) use ($locationId): void {
                $query->where('location_id', $locationId);
            })
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData, $employeeQueries): void {
                $query->where(function ($query) use ($filterData, $employeeQueries): void {
                    $query
                        ->whereAny(['adjustment_date', 'reason'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'type_id',
                            StockAdjustmentTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereHas(
                            'employee',
                            $employeeQueries->searchByFirstAndLastName($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['stock_adjustment_id'], function ($query) use ($filterData): void {
                $query->where('id', $filterData['stock_adjustment_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getColumns(): string
    {
        return 'id,type_id,reason';
    }

    public function getColumnsForStockCardPrint(): string
    {
        return 'id,type_id,reason,adjustment_date,created_at';
    }

    public function getColumnsForPrint(): string
    {
        return 'id,reason,approved_by_employee_id,type_id,adjustment_date,created_at';
    }

    public function getStockAdjustmentsExport(array $filterData, int $companyId): Collection
    {
        return $this->stockAdjustmentQuery($filterData, $companyId)->get();
    }

    public function getStoreManagerStockAdjustmentsExport(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->storeManagerStockAdjustmentQuery($filterData, $companyId, $locationId)->get();
    }

    private function storeManagerStockAdjustmentQuery(array $filterData, int $companyId, int $locationId): Builder
    {
        $employeeQueries = new EmployeeQueries();

        return StockAdjustment::query()
            ->select('id', 'reason', 'approved_by_employee_id', 'type_id', 'adjustment_date')
            ->with('employee:' . $employeeQueries->getFirstAndLastNameColumns())
            ->whereHas('items', function ($query) use ($locationId): void {
                $query->where('location_id', $locationId);
            })
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData, $employeeQueries): void {
                $query->where(function ($query) use ($filterData, $employeeQueries): void {
                    $query
                        ->whereAny(['adjustment_date', 'reason'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'type_id',
                            StockAdjustmentTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereHas(
                            'employee',
                            $employeeQueries->searchByFirstAndLastName($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['stock_adjustment_id'], function ($query) use ($filterData): void {
                $query->where('id', $filterData['stock_adjustment_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function stockAdjustmentQuery(array $filterData, int $companyId): Builder
    {
        $employeeQueries = new EmployeeQueries();
        $mediaQueries = new MediaQueries();

        return StockAdjustment::query()
            ->select('id', 'reason', 'approved_by_employee_id', 'type_id', 'adjustment_date')
            ->with([
                'importRecord:' . $this->getMorphRelatedTableColumns(),
                'importRecord.media:' . $mediaQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData, $employeeQueries): void {
                $query->where(function ($query) use ($filterData, $employeeQueries): void {
                    $query
                        ->whereAny(['adjustment_date', 'reason'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'type_id',
                            StockAdjustmentTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereHas(
                            'employee',
                            $employeeQueries->searchByFirstAndLastName($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['stock_adjustment_id'], function ($query) use ($filterData): void {
                $query->where('id', $filterData['stock_adjustment_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getMorphRelatedTableColumns(): string
    {
        return 'id,module_id,module_type,status,records_in_file,records_imported,records_failed';
    }
}
