<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem\Resources;

use App\Domains\Product\Services\ProductService;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StockTransferItem;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockTransferItem $stockTransferItem */
        $stockTransferItem = $this;

        /** @var Product $product */
        $product = $stockTransferItem->product;

        $productService = resolve(ProductService::class);
        [$color, $size] = $productService->getColorAndSize($product);

        /** @var Collection $transactions */
        $transactions = $stockTransferItem->transactions;

        return [
            'product_name' => $product->name,
            'color' => $color,
            'size' => $size,
            'product_upc' => $product->upc,
            'quantity' => $stockTransferItem->quantity,
            'received_quantity' => $stockTransferItem->received_quantity,
            'transferred_quantity' => $stockTransferItem->received_quantity,
            'remarks' => implode(', ', $this->getRemarks($transactions)),
        ];
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
