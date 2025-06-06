<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\Domains\Product\Services\ProductService;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemTransaction;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

class StockTransferPrintService
{
    public function __construct(
        protected StockTransferQueries $stockTransferQueries
    ) {
    }

    public function printStockTransfer(
        int $stockTransferId,
        string $transferType,
        int $companyId,
        ?int $storeId,
        ?int $warehouseId
    ): string {
        if ('OUT' !== $transferType && 'IN' !== $transferType) {
            throw new RedirectBackWithErrorException('Unsupported transfer type.');
        }

        $productVariant = config('app.product_variant');

        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);

        $stockTransfer = $this->stockTransferQueries->getByIdForPrint($stockTransferId, $companyId);

        $stockTransferBarcode = [
            'transfer_order_barcode' => null,
            'request_order_barcode' => null,
            'transfer_in_barcode' => null,
            'transfer_out_barcode' => null,
        ];

        if (null !== $stockTransfer->transfer_order_number) {
            $stockTransferBarcode['transfer_order_barcode'] = base64_encode(
                $barcodeGeneratorPNG->getBarcode(
                    $stockTransfer->transfer_order_number,
                    $barcodeGeneratorPNG::TYPE_CODE_128,
                    2,
                    35
                )
            );
        }

        if (null !== $stockTransfer->request_order_number) {
            $stockTransferBarcode['request_order_barcode'] = base64_encode(
                $barcodeGeneratorPNG->getBarcode(
                    $stockTransfer->request_order_number,
                    $barcodeGeneratorPNG::TYPE_CODE_128,
                    2,
                    35
                )
            );
        }

        if (null !== $stockTransfer->transfer_in_number) {
            $stockTransferBarcode['transfer_in_barcode'] = base64_encode(
                $barcodeGeneratorPNG->getBarcode(
                    $stockTransfer->transfer_in_number,
                    $barcodeGeneratorPNG::TYPE_CODE_128,
                    2,
                    35
                )
            );
        }

        if (null !== $stockTransfer->transfer_out_number) {
            $stockTransferBarcode['transfer_out_barcode'] = base64_encode(
                $barcodeGeneratorPNG->getBarcode(
                    $stockTransfer->transfer_out_number,
                    $barcodeGeneratorPNG::TYPE_CODE_128,
                    2,
                    35
                )
            );
        }

        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);

        if ($storeId) {
            $stockTransferCheckRequestService->checkPrintTransferType($stockTransfer, $transferType, $storeId);
        }

        if ($warehouseId) {
            $stockTransferCheckRequestService->checkWarehouseManagerPrintTransferType(
                $stockTransfer,
                $transferType,
                $warehouseId
            );
        }

        $stockTransferItemData = $this->getFormattedData($stockTransfer->items);
        $receiver = $stockTransfer->receivedBy;

        $receivedBy = $receiver ? $receiver->user->employee->getFullName() . ' (' . $receiver->user->employee->staff_id . ')' : 'N/A';

        return view('prints.stock_transfer', [
            'stockTransfer' => $stockTransfer,
            'receivedBy' => $receivedBy,
            'statusManagedBy' => $this->getStatusManagesBy($stockTransfer),
            'stockTransferBarcode' => $stockTransferBarcode,
            'stockTransferItems' => $stockTransferItemData,
            'transferTypeStatus' => $transferType,
            'transferType' => StockTransferTypes::getFormattedCaseName($stockTransfer->transfer_type),
            'staticTransferTypeReceived' => StatusTypes::RECEIVED->value,
            'staticTransferTypeShipped' => StatusTypes::SHIPPED->value,
            'staticTransferTypeDiscrepancy' => StatusTypes::DISCREPANCY->value,
            'staticTransferTypeClosed' => StatusTypes::CLOSED->value,
            'currentStatus' => StatusTypes::getFormattedCaseName($stockTransfer->status),
            'productVariant' => $productVariant,
        ])->render();
    }

    public function getFormattedData(Collection $stockTransferItems): array
    {
        $stockTransferItemsData = [];
        if (config('app.product_variant')) {
            $noArticleItems = $stockTransferItems->whereNull('product.masterProduct.article_number');
            $stockTransferItems = $stockTransferItems->whereNotNull('product.masterProduct.article_number')->groupBy(
                'product.masterProduct.article_number'
            );
        } else {
            $noArticleItems = $stockTransferItems->whereNull('product.article_number');
            $stockTransferItems = $stockTransferItems->whereNotNull('product.article_number')->groupBy(
                'product.article_number'
            );
        }

        foreach ($stockTransferItems as $stockTransferItem) {
            $stockTransferItemsData[] = $this->preparedWithGroupedArticleNumbers($stockTransferItem);
        }

        foreach ($noArticleItems as $noArticleItem) {
            $stockTransferItemsData[] = $this->preparedWithoutArticleNumber($noArticleItem);
        }

        return $stockTransferItemsData;
    }

    private function getStatusManagesBy(StockTransfer $stockTransfer): array
    {
        $transactions = $stockTransfer->transactions;
        $approvedBy = $transactions->where(
            'new_status',
            StatusTypes::APPROVED->value
        )->first()?->user->employee->getFullName();
        $closedBy = $transactions->where(
            'new_status',
            StatusTypes::CLOSED->value
        )->first()?->user->employee->getFullName();
        $shippedBy = $transactions->where(
            'new_status',
            StatusTypes::SHIPPED->value
        )->first()?->user->employee->getFullName();
        $discrepancyBy = $transactions->where(
            'new_status',
            StatusTypes::DISCREPANCY->value
        )->first()?->user->employee->getFullName();
        $cancelledBy = $transactions->where(
            'new_status',
            StatusTypes::CANCELLED->value
        )->first()?->user->employee->getFullName();

        return [
            'approved_by' => $approvedBy ?? 'N/A',
            'closed_by' => $closedBy ?? 'N/A',
            'shipped_by' => $shippedBy ?? 'N/A',
            'discrepancy_by' => $discrepancyBy ?? 'N/A',
            'cancelled_by' => $cancelledBy ?? 'N/A',
        ];
    }

    private function preparedWithGroupedArticleNumbers(Collection $stockTransferItems): array
    {
        $product = $stockTransferItems->first()->product;
        $packageType = $stockTransferItems->first()->packageType;

        return [
            'name' => $product->name,
            'products' => $this->getColorAndSizeOfProducts($stockTransferItems),
            'article_number' => config(
                'app.product_variant'
            ) ? $product->masterProduct->article_number : $product->article_number,
            'quantity' => $stockTransferItems->sum('quantity'),
            'received_quantity' => $stockTransferItems->sum('received_quantity'),
            'package_type' => $packageType ? $packageType->name : null,
            'package_quantity' => $stockTransferItems->sum('package_quantity'),
        ];
    }

    private function preparedWithoutArticleNumber(StockTransferItem $noArticleItem): array
    {
        /** @var Product $product */
        $product = $noArticleItem->product;

        /** @var ?StockTransferItemTransaction $transaction */
        $transaction = $noArticleItem->transaction;

        $derivative = $noArticleItem->unitOfMeasureDerivative;
        $packageType = $noArticleItem->packageType;

        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
        }

        $productService = resolve(ProductService::class);

        return [
            'name' => $product->name,
            'products' => [
                [
                    'upc' => $product->upc,
                    'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
                    'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
                    'attributes' => $productService->getAttributesArray($product),
                    'derivative' => $derivative ? $derivative->name : null,
                    'quantity' => $noArticleItem->quantity,
                    'received_quantity' => $noArticleItem->received_quantity,
                    'remarks' => $transaction?->remarks,
                ],
            ],
            'article_number' => config(
                'app.product_variant'
            ) ? $masterProduct?->article_number ?? 'N/A' : $product?->article_number ?? 'N/A',
            'quantity' => $noArticleItem->quantity,
            'received_quantity' => $noArticleItem->received_quantity,
            'package_type' => $packageType ? $packageType->name : null,
            'package_quantity' => $noArticleItem->package_quantity,
        ];
    }

    private function getColorAndSizeOfProducts(Collection $stockTransferItem): array
    {
        $productService = resolve(ProductService::class);
        $productColorAndSize = [];

        foreach ($stockTransferItem as $stockTransferItem) {
            /** @var ?StockTransferItemTransaction $transaction */
            $transaction = $stockTransferItem->transaction;

            $product = $stockTransferItem->product;
            $derivative = $stockTransferItem->unitOfMeasureDerivative;

            $productColorAndSize[] = [
                'upc' => $product->upc,
                'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
                'attributes' => $productService->getAttributesArray($product),
                'derivative' => $derivative ? $derivative->name : null,
                'quantity' => $stockTransferItem->quantity,
                'received_quantity' => $stockTransferItem->received_quantity,
                'remarks' => $transaction?->remarks,
            ];
        }

        return $productColorAndSize;
    }
}
