<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem\Resources;

use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StockTransferItem;
use App\Models\StoreManager;
use App\Models\UnitOfMeasureDerivative;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferItemsListResource extends JsonResource
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

        /** @var Collection $transactions */
        $transactions = $stockTransferItem->transactions;

        /** @var Product $product */
        $product = $stockTransferItem->product;

        $mimeType = $stockTransferItem->getFirstMedia('discrepancy_proof')?->mime_type;

        /** @var ?UnitOfMeasureDerivative $derivative */
        $derivative = $stockTransferItem->unitOfMeasureDerivative;

        return [
            'id' => $stockTransferItem->id,
            'product_name' => $product->name,
            'product_color' => config('app.product_variant') ? null : $product->color->name ?? 'N/A',
            'product_size' => config('app.product_variant') ? null : $product->size->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'product_upc' => $product->upc,
            'derivative' => $derivative instanceof UnitOfMeasureDerivative ? $derivative->name : null,
            'quantity' => $stockTransferItem->quantity,
            'received_quantity' => $stockTransferItem->received_quantity,
            'discrepancy_type' => $stockTransferItem->discrepancy_type,
            'discrepancy_proof' => $stockTransferItem->getDiskBasedFirstMediaUrl('discrepancy_proof'),
            'is_extra_item' => $stockTransferItem->is_extra_item,
            'mime_type' => $mimeType,
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
