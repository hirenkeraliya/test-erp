<?php

declare(strict_types=1);

namespace App\Domains\Sales\Resources;

use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SalesListForStoreManagerApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $sale = $this->resource;

        if ($sale instanceof Sale) {
            /** @var Collection $saleItems */
            $saleItems = $sale->getSaleItems();
            $promoters = $saleItems->map(fn ($saleItem): string => Promoter::getPromoters($saleItem))->unique();
        }

        if ($sale instanceof SaleReturn) {
            /** @var Collection $saleReturnItems */
            $saleReturnItems = $sale->getSaleReturnItems();
            $promoters = $saleReturnItems->map(
                fn ($saleReturnItem): string => Promoter::getPromoters($saleReturnItem->saleItem)
            )->unique();
        }

        $totalAmount = ('Sale' === $sale->type) ? (float) $sale->total_amount_paid : (float) $sale->total_price_paid;

        $receiptId = ('Sale' === $sale->type) ? $sale->offline_sale_id : $sale->offline_sale_return_id;

        return [
            'id' => $sale->id,
            'receipt_id' => $receiptId,
            'sales_amount' => $totalAmount,
            'type' => $sale->type,
            'promoters' => $promoters ?? null,
        ];
    }
}
