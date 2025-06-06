<?php

declare(strict_types=1);

namespace App\Domains\SaleItem\Resources;

use App\Models\Employee;
use App\Models\Member;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EmployeeSalesReportListResource extends JsonResource
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

        /** @var Employee $employee */
        $employee = $member->employee;

        /** @var Collection $mismatches */
        $mismatches = $sale->mismatches;

        return [
            'id' => $saleItem->getKey(),
            'employee' => $employee->getFullName(),
            'mobile_number' => $employee->getMobileNumber(),
            'product' => $product->getName(),
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'upc' => $product->getUpc(),
            'units_sold' => $saleItem->getQuantity(),
            'units_returned' => $saleItem->getReturnedQuantity(),
            'price' => $product->getRetailPrice(),
            'sale_mismatches' => PosMismatch::getPreparedMismatches($mismatches),
            'attributes' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
