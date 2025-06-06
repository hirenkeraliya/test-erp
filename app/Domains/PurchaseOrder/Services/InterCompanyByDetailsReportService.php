<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Product\Services\ProductService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Exports\InterCompanyTransferReportByDetailsExport;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InterCompanyByDetailsReportService
{
    public function renderPreparedByDetails(
        array $filterData,
        Company $company,
        Location $location,
        bool $displayPurchaseCost
    ): string {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $interCompanyStockTransfers = $purchaseOrderQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $company->id
        );

        $interCompanyStockTransferData = $this->preparedByDetails($interCompanyStockTransfers, $filterData);

        $interCompanyStockTransferData->push([
            'transfer_date' => 'Total',
            'delivery_order_numbers' => '',
            'sales_order_number' => '',
            'purchase_order_number' => '',
            'invoice_number' => '',
            'reference_number' => '',
            'transfer_type' => '',
            'status' => '',
            'external_company_name' => '',
            'external_location_name' => '',
            'quantity' => CommonFunctions::truncateDecimal($interCompanyStockTransferData->sum('quantity')),
            'transferred_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransferData->sum('transferred_quantity')
            ),
            'purchase_cost' => '',
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransferData->sum('total_purchase_cost')
            ),
            'remark' => '',
        ]);

        $columns = [
            'Transfer Date',
            'Delivery Order Number',
            'Sales Order Number',
            'Purchase Order Number',
            'Invoice Number',
            'Reference Number',
            'Transfer Type',
            'Status',
            'External Company Name',
            'External Location Name	',
            'Quantity',
            'Received Quantity',
            'Purchase Cost',
            'Total Purchase Cost',
            'Remark',
        ];

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.inter_company_stock_transfer_by_details', [
            'interCompanyStockTransfersData' => $interCompanyStockTransferData,
            'location' => $location,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'displayPurchaseCost' => $displayPurchaseCost,
            'filterBy' => $interCompanyCustomReportService->filterBy($filterData, $company->id),
            'transferType' => $interCompanyCustomReportService->transferType($filterData),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function preparedByDetails(Collection $interCompanyStockTransfers, array $filterData): Collection
    {
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        return $interCompanyStockTransfers->map(fn ($interCompanyStockTransfer): array => [
            'transfer_date' => $interCompanyStockTransfer->created_at->format('d-m-Y'),
            'delivery_order_numbers' => $interCompanyStockTransfer->fulfillments->pluck(
                'delivery_order_number'
            )->filter()->unique()->implode(', '),
            'sales_order_number' => ($interCompanyStockTransfer->order_type === OrderTypes::PURCHASE_ORDER->value) ? $interCompanyStockTransfer->external_order_number : $interCompanyStockTransfer->order_number,
            'purchase_order_number' => ($interCompanyStockTransfer->order_type === OrderTypes::PURCHASE_ORDER->value) ? $interCompanyStockTransfer->order_number : $interCompanyStockTransfer->external_order_number,
            'invoice_number' => $interCompanyStockTransfer->fulfillments->pluck(
                'purchaseOrderInvoice.invoice_number'
            )->filter()->unique()->implode(', '),
            'reference_number' => $interCompanyStockTransfer->reference_number,
            'order_number' => $interCompanyStockTransfer->order_number,
            'external_order_number' => $interCompanyStockTransfer->external_order_number,
            'transfer_type' => OrderTypes::getFormattedCaseName($interCompanyStockTransfer->order_type),
            'status' => Statuses::getFormattedCaseName($interCompanyStockTransfer->status),
            'external_company_name' => $interCompanyStockTransfer->externalCompany?->name,
            'external_location_name' => $interCompanyCustomReportService->getToLocation($interCompanyStockTransfer),
            'transfer_from_and_to' => $this->preparedProductRecords($interCompanyStockTransfer->items),
            'quantity' => CommonFunctions::truncateDecimal($interCompanyStockTransfer->items->sum('quantity')),
            'transferred_quantity' => CommonFunctions::truncateDecimal(
                $interCompanyStockTransfer->items->sum(fn ($item) => $item->transferred_quantity)
            ),
            'purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransfer->items->sum('purchase_cost') / $interCompanyStockTransfer->items->count()
            ),
            'total_purchase_cost' => CommonFunctions::currencyFormat(
                $interCompanyStockTransfer->items->sum(
                    fn ($item): int|float => $item->purchase_cost * $item->transferred_quantity
                )
            ),
            'remark' => $interCompanyStockTransfer->remarks,
        ]);
    }

    public function exportStockTransferReportByDetailsExport(
        int $companyId,
        array $filterData,
        string $filename,
        Location $location,
        bool $displayPurchaseCost,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $interCompanyCustomReportService = resolve(InterCompanyCustomReportService::class);

        $company = $companyQueries->getNameAndCodeById($companyId);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $stockTransfers = $purchaseOrderQueries->getByDateAndLocationWithStockTransferAndProducts(
            $filterData,
            $companyId
        );

        $interCompanyStockTransferData = $this->preparedByDetails($stockTransfers, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);
        $filterBy = $interCompanyCustomReportService->filterBy($filterData, $company->id);
        $transferType = $interCompanyCustomReportService->transferType($filterData);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return Excel::download(
            new InterCompanyTransferReportByDetailsExport(
                $interCompanyStockTransferData,
                $location,
                $dateRange,
                $company,
                $displayPurchaseCost,
                $filterBy,
                $transferType,
                $currency->getSymbol()
            ),
            $filename
        );
    }

    private function preparedProductRecords(Collection $interCompanyStockTransferItems): array
    {
        $groupBy = config('app.product_variant') ? 'product.masterProduct.article_number' : 'product.article_number';

        return $interCompanyStockTransferItems->groupBy($groupBy)->map(fn ($items): array => [
            'name' => $items->first()->product->name,
            'article_number' => config(
                'app.product_variant'
            ) ? $items->first()->product?->masterProduct?->article_number : $items->first()->product->article_number,
            'total_quantity' => CommonFunctions::truncateDecimal($items->sum('transferred_quantity')),
            'color_wise_products' => $this->preparedProductByColorRecords($items),
        ])->toArray();
    }

    private function preparedProductByColorRecords(Collection $items): array
    {
        $productService = resolve(ProductService::class);

        return $items->map(function ($item) use ($productService): array {
            /** @var Product $product */
            $product = $item->product;

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            return [
                'upc' => $product->upc,
                ...$colorSizeOrAttributeData,
                'quantity' => CommonFunctions::truncateDecimal((float) $item->quantity),
                'transferred_quantity' => CommonFunctions::truncateDecimal((float) $item->transferred_quantity),
            ];
        })->toArray();
    }
}
