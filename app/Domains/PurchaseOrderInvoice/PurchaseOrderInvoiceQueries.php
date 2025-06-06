<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Services\ProductVariantFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderInvoiceTransaction\PurchaseOrderInvoiceTransactionQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\Size\SizeQueries;
use App\Models\PurchaseOrderInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderInvoiceQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderInvoiceTransactionQueries = resolve(PurchaseOrderInvoiceTransactionQueries::class);

        return PurchaseOrderInvoice::query()
            ->select('id', 'purchase_order_id', 'status', 'created_by_company_id', 'invoice_number', 'created_at')
            ->when(null !== $filterData['location_id'], function ($query) use (
                $purchaseOrderQueries,
                $companyId,
                $filterData
            ): void {
                $query->whereHas(
                    'purchaseOrder',
                    $purchaseOrderQueries->filterByCompanyAndLocation($companyId, (int) $filterData['location_id'])
                );
            }, function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'transactions:' . $purchaseOrderInvoiceTransactionQueries->getNameColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('invoice_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(array $purchaseOrderInvoiceData): PurchaseOrderInvoice
    {
        return PurchaseOrderInvoice::create($purchaseOrderInvoiceData);
    }

    public function getById(int $purchaseOrderInvoiceId, int $companyId): PurchaseOrderInvoice
    {
        return PurchaseOrderInvoice::query()
            ->select('id', 'purchase_order_id', 'status', 'created_by_company_id', 'company_id')
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderInvoiceId);
    }

    public function getByIdAndLocation(
        int $purchaseOrderInvoiceId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderInvoice {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderInvoice::query()
            ->select('id', 'purchase_order_id', 'status', 'created_by_company_id', 'company_id')
            ->where('company_id', $companyId)
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->findOrFail($purchaseOrderInvoiceId);
    }

    public function cancelInvoice(PurchaseOrderInvoice $purchaseOrderInvoice, int $status): void
    {
        $purchaseOrderInvoice->status = $status;
        $purchaseOrderInvoice->update();
    }

    public function updateInvoiceStatus(PurchaseOrderInvoice $purchaseOrderInvoice, int $status): void
    {
        $purchaseOrderInvoice->status = $status;
        $purchaseOrderInvoice->update();
    }

    public function getByIdForPaid(int $purchaseOrderInvoiceId, int $companyId): PurchaseOrderInvoice
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderInvoice::query()
            ->select(
                'id',
                'purchase_order_id',
                'external_purchase_order_invoice_id',
                'status',
                'created_by_company_id',
                'company_id'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderInvoiceId);
    }

    public function getByIdAndLocationForPaid(
        int $purchaseOrderInvoiceId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderInvoice {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderInvoice::query()
            ->select(
                'id',
                'purchase_order_id',
                'external_purchase_order_invoice_id',
                'status',
                'created_by_company_id',
                'company_id'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderInvoiceId);
    }

    public function getByIdForSent(int $purchaseOrderInvoiceId, int $companyId): PurchaseOrderInvoice
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        return PurchaseOrderInvoice::query()
            ->select(
                'id',
                'purchase_order_id',
                'external_purchase_order_invoice_id',
                'status',
                'created_by_company_id',
                'company_id',
                'invoice_number'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderInvoiceId);
    }

    public function getByIdAndLocationForSent(
        int $purchaseOrderInvoiceId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderInvoice {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        return PurchaseOrderInvoice::query()
            ->select(
                'id',
                'purchase_order_id',
                'external_purchase_order_invoice_id',
                'status',
                'created_by_company_id',
                'company_id',
                'invoice_number'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderInvoiceId);
    }

    public function updateExternalInvoiceId(int $invoiceId, int $externalInvoiceId, int $companyId): void
    {
        $purchaseOrderInvoice = $this->getById($invoiceId, $companyId);
        $purchaseOrderInvoice->external_purchase_order_invoice_id = $externalInvoiceId;
        $purchaseOrderInvoice->save();
    }

    public function getByIdForPrint(int $invoiceId, int $companyId): PurchaseOrderInvoice
    {
        $mediaQueries = resolve(MediaQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);

        return PurchaseOrderInvoice::query()
            ->select(
                'id',
                'purchase_order_id',
                'external_purchase_order_invoice_id',
                'status',
                'created_by_company_id',
                'company_id',
                'invoice_number',
                'created_at'
            )
            ->with(
                'company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'company.media:' . $mediaQueries->getBasicColumnNames(),
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'fulfillments.items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'fulfillments.items.product:' . $productQueries->getBasicColumnNamesForPurchaseOrderInvoice(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($invoiceId);
    }

    public function getByIdAndLocationForPrint(
        int $invoiceId,
        int $companyId,
        ?int $locationId,
    ): PurchaseOrderInvoice {
        $mediaQueries = resolve(MediaQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
            'company.media:' . $mediaQueries->getBasicColumnNames(),
            'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
            'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
            'purchaseOrder.externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
            'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
            'fulfillments.items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
            'fulfillments.items.purchaseOrderItem:' . $purchaseOrderItemQueries->getColumnForPrintInvoice(),
            'fulfillments.items.product:' . $productQueries->getBasicColumnNamesForPurchaseOrderInvoice(),
            'purchaseOrder.location',
            'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'fulfillments.items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'fulfillments.items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'fulfillments.items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'fulfillments.items.product.color:' . $colorQueries->getBasicColumnNames(),
                'fulfillments.items.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return PurchaseOrderInvoice::query()
            ->select(
                'id',
                'purchase_order_id',
                'external_purchase_order_invoice_id',
                'status',
                'created_by_company_id',
                'company_id',
                'invoice_number',
                'created_at'
            )
            ->with($relations)
            ->when(null !== $locationId, function ($query) use (
                $purchaseOrderQueries,
                $companyId,
                $locationId
            ): void {
                if (! $locationId) {
                    return;
                }

                $query->whereHas(
                    'purchaseOrder',
                    $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId)
                );
            })
            ->where('company_id', $companyId)
            ->findOrFail($invoiceId);
    }

    public function allInvoiceStatusCount(array $filterData, int $companyId): Collection
    {
        return $this->getStatusCount($filterData, $companyId)->get();
    }

    private function getStatusCount(array $filterData, int $companyId): Builder
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderInvoice::query()
            ->select('status', 'purchase_order_id', DB::raw('COUNT(*) as count'))
            ->when(null !== $filterData['location_id'], function ($query) use (
                $purchaseOrderQueries,
                $companyId,
                $filterData
            ): void {
                $query->whereHas(
                    'purchaseOrder',
                    $purchaseOrderQueries->filterByCompanyAndLocation($companyId, (int) $filterData['location_id'])
                );
            }, function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->groupBy('status')
            ->with(['purchaseOrder:' . $purchaseOrderQueries->getBasicColumn()])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('invoice_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
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

    public function getPurchaseOrderInvoicesForReport(array $filterData, int $companyId): Collection
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderFulfillmentsQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);

        return PurchaseOrderInvoice::query()
            ->select('id', 'purchase_order_id', 'status', 'created_by_company_id', 'invoice_number', 'created_at')
            ->with([
                'fulfillments:' . $purchaseOrderFulfillmentsQueries->getBasicColumns(),
                'fulfillments.purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'fulfillments.items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'fulfillments.items.product:' . $productQueries->getBasicColumnNames(),
                'fulfillments.items.purchaseOrderItem:' . $purchaseOrderItemQueries->getColumnForPrintInvoice(),
            ])
            ->whereHas('fulfillments.purchaseOrder', function ($query) use (
                $companyId,
                $filterData,
                $purchaseOrderQueries,
            ): void {
                $query->where($purchaseOrderQueries->filterByCompany($companyId))
                    ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                        $query->where('external_location_id', (int) $filterData['external_location_id']);
                    })
                    ->when((int) $filterData['external_company_id'], function ($query) use ($filterData): void {
                        $query->where('external_company_id', (int) $filterData['external_company_id']);
                    })
                    ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                        $query->where('location_id', (int) $filterData['location_id']);
                    });
            })
            ->whereHas('fulfillments.items', function ($query) use ($filterData, $productVariantFilterService): void {
                $query->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                    $query->where('product_id', $filterData['product_id']);
                })
                    ->when(null !== $filterData['article_number'], function ($query) use (
                        $filterData,
                        $productVariantFilterService
                    ): void {
                        $query->whereIn(
                            'product_id',
                            $productVariantFilterService->filterByDepartmentAndBrandAndArticleNumber(
                                'article_number',
                                $filterData['article_number']
                            )
                        );
                    })
                    ->when(null !== $filterData['product_collection_id'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            $query->select('product_id')
                                ->from('product_collection_products')
                                ->where('product_collection_id', (int) $filterData['product_collection_id']);
                        });
                    });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->get();
    }

    public function getColumnForCustomReport(): string
    {
        return 'id,invoice_number';
    }
}
