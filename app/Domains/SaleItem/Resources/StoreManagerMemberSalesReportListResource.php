<?php

declare(strict_types=1);

namespace App\Domains\SaleItem\Resources;

use App\Models\Color;
use App\Models\Member;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StoreManagerMemberSalesReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var SaleItem $saleItem */
        $saleItem = $this;

        /** @var Product $product */
        $product = $saleItem->product;

        /** @var Sale $sale */
        $sale = $saleItem->sale;

        /** @var Member $member */
        $member = $sale->member;

        /** @var Collection $mismatches */
        $mismatches = $sale->mismatches;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Size $size */
        $size = $product->size;

        return [
            'id' => $saleItem->getKey(),
            'member' => $member->getFullName(),
            'mobile_number' => $member->getMobileNumber(),
            'product' => $product->getName(),
            'color' => $color instanceof Color ? $color->getName() : null,
            'size' => $size instanceof Size ? $size->getName() : null,
            'upc' => $product->getUpc(),
            'units_sold' => $saleItem->getQuantity(),
            'units_returned' => $saleItem->getReturnedQuantity(),
            'price' => $product->getRetailPrice(),
            'sale_mismatches' => PosMismatch::getPreparedMismatches($mismatches),
        ];
    }
}
