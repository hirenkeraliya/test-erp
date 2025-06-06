<?php

declare(strict_types=1);

namespace App\Domains\SerialNumber;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Models\SerialNumber;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SerialNumberQueries
{
    public function getWithBasicColumns(int $companyId): Collection
    {
        return SerialNumber::select('id', 'serial_number as name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        return fn ($query) => $query->select('id', 'serial_number', 'product_id')
            ->where('company_id', $companyId);
    }

    public function filterByCompanyIdAndStatus(int $companyId, int $status): Closure
    {
        return fn ($query) => $query->select('id', 'serial_number', 'product_id')
            ->where('company_id', $companyId)
            ->where('status', $status);
    }

    public function checkActiveSerialNumberExists(int $productId, int $companyId, string $serialNumber): bool
    {
        return SerialNumber::select('id')
            ->where('product_id', $productId)
            ->where('company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->where('status', SerialNumberStatus::ACTIVE->value)
            ->exists();
    }

    public function getByCompanyIdAndSerialNumber(int $companyId, string $serialNumber): ?SerialNumber
    {
        return SerialNumber::select('id', 'company_id', 'product_id', 'status', 'serial_number')
            ->where('company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->first();
    }

    public function getByCompanyIdAndSerialNumberWithStatusSold(int $companyId, string $serialNumber): bool
    {
        return SerialNumber::select('id', 'company_id', 'product_id', 'status', 'serial_number')
            ->where('company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->where('status', SerialNumberStatus::SOLD->value)
            ->exists();
    }

    public function checkSerialNumberBySoldStatus(int $productId, int $companyId, string $serialNumber): bool
    {
        return SerialNumber::select('id')
            ->where('product_id', $productId)
            ->where('company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->where('status', SerialNumberStatus::SOLD->value)
            ->exists();
    }

    public function firstOrCreate(int $productId, int $companyId, string $serialNumber): SerialNumber
    {
        return SerialNumber::firstOrCreate([
            'product_id' => $productId,
            'company_id' => $companyId,
            'serial_number' => $serialNumber,
        ]);
    }

    public function updateStatus(SerialNumber $serialNumber, int $status): void
    {
        $serialNumber->status = $status;
        $serialNumber->save();
    }

    public function getBySerialNumber(int $productId, string $serialNumber): SerialNumber
    {
        return SerialNumber::select('id', 'serial_number')
            ->where('product_id', $productId)
            ->where('serial_number', $serialNumber)
            ->firstOrFail();
    }

    public function getPaginatedProductSerialNumber(array $filterData, int $companyId): LengthAwarePaginator
    {
        $productQueries = resolve(ProductQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);

        return SerialNumber::query()
            ->select('id', 'serial_number', 'product_id', 'status')
            ->with([
                'product:' . $productQueries->getBasicColumnNames(),
                'inventoryUnit:' . $inventoryUnitQueries->getBasicColumnNames(),
                'inventoryUnit.inventory:' . $inventoryQueries->getBasicColumnNames(),
                'inventoryUnit.inventory.location:id,name,code,type_id',
            ])
            ->when(null !== $filterData['serial_number_id'], function ($query) use ($filterData): void {
                $query->where('id', $filterData['serial_number_id']);
            })
            ->where('company_id', $companyId)
            ->paginate($filterData['per_page']);
    }

    public function checkSerialNumberExists(string $serialNumber, int $companyId): ?SerialNumber
    {
        return SerialNumber::where('company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->first();
    }

    public function updateOrCreate(array $serialNumberData): int
    {
        return SerialNumber::updateOrCreate([
            'serial_number' => $serialNumberData['serial_number'],
        ],
            [
                'company_id' => $serialNumberData['company_id'],
                'product_id' => $serialNumberData['product_id'],
                'status' => $serialNumberData['status'],
            ]
        )->id;
    }

    public function loadRelation(int|string $number, int $companyId): SerialNumber
    {
        $productQueries = resolve(ProductQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return SerialNumber::query()
            ->select('id', 'serial_number', 'product_id', 'status')
            ->with([
                'product:' . $productQueries->getBasicColumnNamesForSerialNumberDetails(),
                'inventoryUnit:' . $inventoryUnitQueries->getBasicColumnNames(),
                'inventoryUnit.inventory:' . $inventoryQueries->getBasicColumnNames(),
                'inventoryUnit.inventory.location:id,name,code,type_id',
                'saleItemUnit:' . $saleItemUnitQueries->getColumnNamesForPos(),
                'saleItemUnit.saleItem:' . $saleItemQueries->getBasicColumnNamesForReturnAndExchangeReport(),
                'saleItemUnit.saleItem.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'saleItemUnit.saleItem.sale:' . $saleQueries->getBasicColumnNamesForSerialNumberDetails(),
                'saleItemUnit.saleItem.sale.member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItemUnit.saleItem.sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'saleItemUnit.saleItem.saleReturnItem:' . $saleReturnItemQueries->getColumnNames(),
                'saleItemUnit.saleItem.saleReturnItem.saleItem.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'saleItemUnit.saleItem.saleReturnItem.saleReturn:' . $saleReturnQueries->getBasicColumnNamesForSerialNumberDetails(),
                'saleItemUnit.saleItem.saleReturnItem.saleReturn.member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'saleItemUnit.saleItem.saleReturnItem.saleReturn.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('serial_number', $number)
            ->firstOrFail();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,status,serial_number,product_id';
    }

    public function setAsDeleteStatus(SerialNumber $serialNumber): void
    {
        $serialNumber->status = SerialNumberStatus::DELETED->value;
        $serialNumber->save();
    }
}
