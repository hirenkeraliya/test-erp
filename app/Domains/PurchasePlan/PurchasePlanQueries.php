<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchasePlanItem\PurchasePlanItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\Product;
use App\Models\PurchasePlan;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchasePlanQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getPurchasePlans($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function exportPurchasePlan(array $filterData, int $companyId): Collection
    {
        return $this->getPurchasePlans($filterData, $companyId)->get();
    }

    public function getLocationColumnName(): string
    {
        return 'id,vendor_id,location_id,total_amount,reference_number,remarks,status,plan_number';
    }

    public function addNew(array $purchasePlanData): PurchasePlan
    {
        return PurchasePlan::create($purchasePlanData);
    }

    public function getByIdWithItems(int $purchasePlanId): PurchasePlan
    {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        if (config('app.product_variant')) {
            return PurchasePlan::query()
                ->select(
                    'id',
                    'vendor_id',
                    'company_id',
                    'location_id',
                    'total_amount',
                    'reference_number',
                    'plan_number',
                    'remarks',
                    'status',
                )
                ->with([
                    'location:' . $locationQueries->getNameColumnName(),
                    'vendor:' . $vendorQueries->getBasicColumnNames(),
                    'items:' . $purchasePlanItemQueries->getBasicColumnNames(),
                    'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->findOrFail($purchasePlanId);
        }

        return PurchasePlan::query()
            ->select(
                'id',
                'vendor_id',
                'location_id',
                'total_amount',
                'reference_number',
                'remarks',
                'plan_number',
                'status',
            )
            ->with([
                'location:' . $locationQueries->getNameColumnName(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
                'items:' . $purchasePlanItemQueries->getBasicColumnNames(),
                'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            ])
            ->findOrFail($purchasePlanId);
    }

    public function update(array $purchasePlanData, int $purchasePlanId, Collection $products): void
    {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);
        $purchasePlan = $this->getByIdWithItems($purchasePlanId);
        $purchasePlan->items()->delete();
        $purchasePlanItems = $purchasePlanData['transfer_items'];
        unset($purchasePlanData['transfer_items']);

        $purchasePlan->update($purchasePlanData);

        foreach ($purchasePlanItems as $purchasePlanItem) {
            /** @var Product $product */
            $product = $products->firstWhere('id', $purchasePlanItem['product_id']);

            $costPrice = $purchasePlanItem['is_product_purchase_cost'] ? (float) $product->purchase_cost : (float) $purchasePlanItem['purchase_cost'];

            $purchasePlanItemQueries->addNew([
                'purchase_plan_id' => $purchasePlanId,
                'product_id' => $purchasePlanItem['product_id'],
                'quantity' => $purchasePlanItem['quantity'],
                'is_product_purchase_cost' => $purchasePlanItem['is_product_purchase_cost'],
                'unit_of_measure_derivative_id' => $purchasePlanItem['unit_of_measure_derivative_id'] ?? null,
                'cost_price' => $costPrice,
                'total_price' => (float) $purchasePlanItem['quantity'] * $costPrice,
            ]);
        }
    }

    public function getById(int $purchasePlanId, int $companyId): PurchasePlan
    {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        return PurchasePlan::query()
            ->select('id', 'vendor_id', 'status', 'location_id', 'total_amount', 'plan_number')
            ->with('items:' . $purchasePlanItemQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($purchasePlanId);
    }

    public function updateStatus(PurchasePlan $purchasePlan, int $status): void
    {
        $purchasePlan->status = $status;
        $purchasePlan->save();
    }

    public function updateTotalAmount(PurchasePlan $purchasePlan, float $totalAmount): void
    {
        $purchasePlan->total_amount = $totalAmount;
        $purchasePlan->save();
    }

    public function getByIdForPrint(int $purchasePlanId, ?int $locationId, int $companyId): PurchasePlan
    {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        if (config('app.product_variant')) {
            return PurchasePlan::query()
                ->select(
                    'id',
                    'vendor_id',
                    'company_id',
                    'location_id',
                    'total_amount',
                    'reference_number',
                    'remarks',
                    'plan_number',
                    'status',
                )
                ->with([
                    'location:' . $locationQueries->getBasicColumnForPurchasePlan(),
                    'vendor:' . $vendorQueries->getBasicColumnNamesForPurchasePlan(),
                    'items:' . $purchasePlanItemQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'location.city:' . $cityQueries->getBasicColumnNames(),
                ])
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                })
                ->where('company_id', $companyId)
                ->findOrFail($purchasePlanId);
        }

        return PurchasePlan::query()
            ->select(
                'id',
                'vendor_id',
                'location_id',
                'company_id',
                'total_amount',
                'reference_number',
                'remarks',
                'plan_number',
                'status',
                'created_at'
            )
            ->with([
                'location:' . $locationQueries->getBasicColumnForPurchasePlan(),
                'vendor:' . $vendorQueries->getBasicColumnNamesForPurchasePlan(),
                'items:' . $purchasePlanItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.product.size:' . $sizeQueries->getBasicColumnNames(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($locationId, function ($query) use ($locationId): void {
                $query->where('location_id', $locationId);
            })
            ->findOrFail($purchasePlanId);
    }

    public function allRequestStatusCount(array $filterData, int $companyId): Collection
    {
        return $this->getStatusCount($filterData, $companyId)->get();
    }

    private function getStatusCount(array $filterData, int $companyId): Builder
    {
        return PurchasePlan::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->groupBy('status')
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->when((int) $filterData['vendor_id'], function ($query) use ($filterData): void {
                $query->where('vendor_id', (int) $filterData['vendor_id']);
            })
            ->when($filterData['plan_number'], function ($query) use ($filterData): void {
                $query->where('plan_number', $filterData['plan_number']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            });
    }

    private function getPurchasePlans(array $filterData, int $companyId): Builder
    {
        $vendorQueries = resolve(VendorQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return PurchasePlan::query()
            ->select(
                'id',
                'vendor_id',
                'company_id',
                'location_id',
                'total_amount',
                'reference_number',
                'plan_number',
                'remarks',
                'status',
                'created_at'
            )
            ->with([
                'location:' . $locationQueries->getNameColumnName(),
                'vendor:' . $vendorQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(
                        ['plan_number', 'reference_number'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    );
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->when((int) $filterData['vendor_id'], function ($query) use ($filterData): void {
                $query->where('vendor_id', (int) $filterData['vendor_id']);
            })
            ->when($filterData['plan_number'], function ($query) use ($filterData): void {
                $query->where('plan_number', $filterData['plan_number']);
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

    public function filterByCompanyId(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,company_id,location_id,vendor_id,plan_number';
    }

    public function getByPurchasePlanIdForCreateEpo(int $purchasePlanId, int $companyId): PurchasePlan
    {
        $columns = explode(',', $this->getColumnsForExternalPurchaseOrders());

        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);

        return PurchasePlan::query()
            ->select(...$columns)
            ->with([
                'items' => function ($query) use (
                    $purchasePlanItemQueries,
                    $productQueries,
                    $colorQueries,
                    $sizeQueries,
                ): void {
                    $query->select(explode(',', $purchasePlanItemQueries->getBasicColumnNames()))
                        ->with([
                            'product:' . $productQueries->getBasicColumnNames(),
                            'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                            'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                        ])
                        ->whereRaw('quantity > (COALESCE(transferred_quantity, 0))');
                },
            ])
            ->where('company_id', $companyId)
            ->findOrFail($purchasePlanId);
    }

    public function getByIdAndCompanyIdWithItems(int $purchasePlanId, int $companyId): PurchasePlan
    {
        $purchasePlanItemQueries = resolve(PurchasePlanItemQueries::class);

        return PurchasePlan::query()
            ->select('id', 'company_id', 'status', 'location_id')
            ->with('items:' . $purchasePlanItemQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($purchasePlanId);
    }

    public function getColumnsForExternalPurchaseOrders(): string
    {
        return 'id,plan_number,location_id,status';
    }
}
