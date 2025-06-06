<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrder;

use App\CommonFunctions;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\ExternalPurchaseOrderItem\ExternalPurchaseOrderItemQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\PurchasePlanItem\PurchasePlanItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\ExternalPurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExternalPurchaseOrderQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return $this->getExternalPurchaseOrders($filterData)->paginate($filterData['per_page']);
    }

    public function getByIdWith(int $externalPurchaseOrderId, int $companyId): ExternalPurchaseOrder
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);

        return ExternalPurchaseOrder::query()
            ->select('id', 'purchase_plan_id', 'order_number', 'status', 'total_amount')
            ->with(['purchasePlan:' . $purchasePlanQueries->getBasicColumnNames()])
            ->whereHas('purchasePlan', $purchasePlanQueries->filterByCompanyId($companyId))
            ->findOrFail($externalPurchaseOrderId);
    }

    public function updateStatus(ExternalPurchaseOrder $externalPurchaseOrder, int $status): void
    {
        $externalPurchaseOrder->status = $status;
        $externalPurchaseOrder->save();
    }

    public function loadRelations(ExternalPurchaseOrder $externalPurchaseOrder): ExternalPurchaseOrder
    {
        $externalPurchaseOrder->refresh();

        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);

        return $externalPurchaseOrder->load('items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames());
    }

    private function getExternalPurchaseOrders(array $filterData): Builder
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);

        return ExternalPurchaseOrder::query()
            ->select('id', 'order_number', 'date', 'notes', 'total_amount', 'status', 'purchase_plan_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['order_number'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->whereHas('purchasePlan', $purchasePlanQueries->filterByCompanyId($filterData['company_id']))
            ->where('purchase_plan_id', $filterData['purchase_plane_id'])
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function addNew(array $externalPurchaseOrderData): ExternalPurchaseOrder
    {
        return ExternalPurchaseOrder::create($externalPurchaseOrderData);
    }

    public function update(array $externalPurchaseOrderData, int $externalPurchaseOrderId): void
    {
        $externalPurchaseOrder = $this->getById($externalPurchaseOrderId);
        $externalPurchaseOrder->update($externalPurchaseOrderData);
    }

    public function getById(int $externalPurchaseOrderId): ExternalPurchaseOrder
    {
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);

        return ExternalPurchaseOrder::query()
            ->select(
                'id',
                'purchase_plan_id',
                'order_number',
                'date',
                'notes',
                'fob',
                'freight_charges',
                'insurance_charges',
                'duty',
                'sst',
                'handling_charges',
                'other_charges',
                'total_amount',
                'status'
            )
            ->with('items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames())
            ->findOrFail($externalPurchaseOrderId);
    }

    public function getByIdWithRelationForEdit(int $externalPurchaseOrderId): ExternalPurchaseOrder
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);

        return ExternalPurchaseOrder::query()
        ->select(
            'id',
            'purchase_plan_id',
            'order_number',
            'date',
            'notes',
            'fob',
            'freight_charges',
            'insurance_charges',
            'duty',
            'sst',
            'handling_charges',
            'other_charges',
            'total_amount',
            'status'
        )
        ->with([
            'purchasePlan:' . $purchasePlanQueries->getBasicColumnNames(),
            'purchasePlan.items:' . $purchasePlanItemQueries->getBasicColumnNames(),
            'purchasePlan.items.product:' . $productQueries->getBasicColumnNames(),
            'purchasePlan.items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
            'purchasePlan.items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            'items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
        ])
        ->findOrFail($externalPurchaseOrderId);
    }

    public function getByIdForPrint(int $externalPurchaseOrderId): ExternalPurchaseOrder
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        return ExternalPurchaseOrder::query()
            ->select('id', 'purchase_plan_id', 'order_number', 'date', 'notes')
            ->with([
                'items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'purchasePlan:' . $purchasePlanQueries->getBasicColumnNames(),
                'purchasePlan.vendor:' . $vendorQueries->getBasicColumnNamesForPurchasePlan(),
                'purchasePlan.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'purchasePlan.company.media:' . $mediaQueries->getBasicColumnNames(),
                'purchasePlan.location:' . $locationQueries->getBasicColumnNames(),
                'purchasePlan.location.city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->findOrFail($externalPurchaseOrderId);
    }

    public function getByIdForPartialReceive(int $externalPurchaseOrderId): ExternalPurchaseOrder
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        return ExternalPurchaseOrder::query()
            ->select('id', 'purchase_plan_id', 'order_number', 'date', 'notes', 'status')
            ->with([
                'items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'purchasePlan:' . $purchasePlanQueries->getBasicColumnNames(),
                'purchasePlan.vendor:' . $vendorQueries->getBasicColumnNamesForPurchasePlan(),
                'purchasePlan.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'purchasePlan.company.media:' . $mediaQueries->getBasicColumnNames(),
                'purchasePlan.location:' . $locationQueries->getBasicColumnNames(),
                'purchasePlan.location.city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->findOrFail($externalPurchaseOrderId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,purchase_plan_id,order_number,date,notes,fob,freight_charges,insurance_charges,duty,sst,handling_charges,other_charges,total_amount,status';
    }

    public function getByPartialReceiveIdForCreateEpopr(int $externalPurchaseOrderId): ExternalPurchaseOrder
    {
        $columns = explode(',', $this->getBasicColumnNames());

        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);

        return ExternalPurchaseOrder::query()
            ->select(...$columns)
            ->with(['items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames()])
            ->findOrFail($externalPurchaseOrderId);
    }

    public function getByIdWithForCancel(int $externalPurchaseOrderId, int $companyId): ExternalPurchaseOrder
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        return ExternalPurchaseOrder::query()
            ->select('id', 'purchase_plan_id', 'order_number', 'status', 'total_amount')
            ->with([
                'purchasePlan:' . $purchasePlanQueries->getBasicColumnNames(),
                'items:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
                'items.purchasePlanItem:' . $purchasePlanItemQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchasePlan', $purchasePlanQueries->filterByCompanyId($companyId))
            ->findOrFail($externalPurchaseOrderId);
    }

    public function getExternalPurchaseOrderStatusCount(array $filterData): Collection
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);

        return ExternalPurchaseOrder::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->where('purchase_plan_id', (int) $filterData['purchase_plane_id'])
            ->whereHas('purchasePlan', $purchasePlanQueries->filterByCompanyId($filterData['company_id']))
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->get();
    }

    public function getByPurchasePlanId(int $purchasePlanId): Collection
    {
        return ExternalPurchaseOrder::query()
            ->select('id', 'status')
            ->whereNot('status', Statuses::CANCELLED->value)
            ->where('purchase_plan_id', $purchasePlanId)
            ->orderBy('id', 'desc')
            ->get();
    }
}
