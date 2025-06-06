<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem\Exports;

use App\Domains\Product\Services\ProductService;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StockTransferItem;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockTransferItemsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $stockTransferItems
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->stockTransferItems->map(
            function (StockTransferItem $stockTransferItem) use ($productService): array {
                /** @var Collection $transactions */
                $transactions = $stockTransferItem->transactions;

                /** @var Product $product */
                $product = $stockTransferItem->product;

                $data = [
                    'product_name' => $product->name,
                    'product_upc' => $product->upc,
                    'quantity' => $stockTransferItem->quantity,
                    'received_quantity' => $stockTransferItem->received_quantity,
                    'transferred_quantity' => $stockTransferItem->received_quantity,
                    'remarks' => implode(', ', $this->getRemarks($transactions)),
                ];

                if (config('app.product_variant')) {
                    return array_merge($data, [
                        'attributes' => $productService->getAttributesForPrint($product),
                    ]);
                }

                return array_merge($data, [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ]);
            }
        );
    }

    public function headings(): array
    {
        $headingColumns = [
            'Product Name',
            'Product UPC',
            'Quantity',
            'Received Quantity',
            'Transferred Quantity',
            'Remarks',
        ];

        if (config('app.product_variant')) {
            return array_merge($headingColumns, ['attributes']);
        }

        return array_merge($headingColumns, ['Color', 'Size']);
    }

    private function getRemarks(Collection $transactions): array
    {
        return $transactions->map(function ($transaction, $index) {
            /** @var Admin|StoreManager|WarehouseManager $user */
            $user = $transaction->user;

            /** @var Employee $employee */
            $employee = $user->employee;

            $user = $employee->getFullName();
            $status = StatusTypes::getFormattedCaseName($transaction->status);

            /** @var Carbon $createdAt */
            $createdAt = $transaction->created_at;

            $transaction->remarks = $index + 1 . ': ' . $transaction->remarks . ' (' . $user . ', ' . $status . ', ' . $createdAt->format(
                'd-m-Y H:i:s'
            ) . ')';

            return $transaction;
        })->pluck('remarks')->toArray();
    }
}
