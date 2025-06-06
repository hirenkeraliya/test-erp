<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemUnit;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SerialNumberDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $serialNumber = $this->resource;

        /** @var Product $product */
        $product = $serialNumber->product;

        /** @var InventoryUnit $inventoryUnit */
        $inventoryUnit = $serialNumber->inventoryUnit;

        /** @var ?Inventory $inventory */
        $inventory = $inventoryUnit->inventory ?? null;
        $location = null;

        if ($inventory instanceof Inventory) {
            /** @var Location $location */
            $location = $inventory->location;

            $locationType = LocationTypes::getFormattedCaseName($location->type_id);
        }

        /** @var ?SaleItemUnit $saleItemUnit */
        $saleItemUnit = $serialNumber->saleItemUnit;
        $saleItem = null;
        $sale = null;
        $saleReturn = null;
        $saleReturnItem = null;

        if ($saleItemUnit instanceof SaleItemUnit) {
            /** @var SaleItem $saleItem */
            $saleItem = $saleItemUnit->saleItem;

            /** @var Sale $sale */
            $sale = $saleItem->sale;

            /** @var ?SaleReturnItem $saleReturnItem */
            $saleReturnItem = $saleItem->saleReturnItem;

            if ($saleReturnItem instanceof SaleReturnItem) {
                /** @var SaleReturn $saleReturn */
                $saleReturn = $saleReturnItem->saleReturn;
            }
        }

        return [
            'serial_number' => $serialNumber->serial_number,
            'status' => SerialNumberStatus::getFormattedCaseName($serialNumber->status),
            'location_id' => $location ? $location->id : null,
            'location_name' => $location ? $location->name . ' (' . $locationType . ')' : null,
            'product_details' => $this->preparedProductDetails($product),
            'warranty_details' => $this->preparedWarrantyDetails($product, $sale),
            'sale_details' => $this->preparedSaleDetails($sale, $saleItem),
            'return_details' => $this->preparedReturnDetails($saleReturn, $saleReturnItem),
        ];
    }

    public function preparedProductDetails(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->getName(),
            'upc' => $product->getUpc(),
        ];
    }

    public function preparedWarrantyDetails(Product $product, ?Sale $sale): ?array
    {
        if (! $sale instanceof Sale || ! $product->warranty_month) {
            return null;
        }

        /** @var Carbon $date */
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
        $expirationDate = $date->addMonths((int) $product->warranty_month);

        return [
            'expiration_date' => $expirationDate->format('Y-m-d H:i:s'),
            'duration_in_months' => $product->warranty_month,
            'status' => $expirationDate->isPast() ? 'InActive' : 'Active',
        ];
    }

    public function preparedSaleDetails(?Sale $sale, ?SaleItem $saleItem): ?array
    {
        if (! $sale instanceof Sale || ! $saleItem instanceof SaleItem) {
            return null;
        }

        return [
            'id' => $sale->id,
            'offline_id' => $sale->offline_sale_id,
            'location_id' => $sale->counterUpdate?->counter?->location?->id,
            'location_name' => $sale->counterUpdate?->counter?->location?->name,
            'happened_at' => $sale->happened_at,
            'promoter_details' => $saleItem->promoters->map(fn (Promoter $promoter): array => [
                'id' => $promoter->employee?->id,
                'name' => $promoter->employee?->getFullName(),
                'staff_id' => $promoter->employee?->staff_id,
            ]),
            'member_info' => $sale->member ? [
                'id' => $sale->member->id,
                'first_name' => $sale->member->first_name,
                'last_name' => $sale->member->last_name,
                'email' => $sale->member->email,
                'mobile_number' => $sale->member->mobile_number,
                'card_number' => $sale->member->card_number,
            ] : null,
        ];
    }

    public function preparedReturnDetails(?SaleReturn $saleReturn, ?SaleReturnItem $saleReturnItem): ?array
    {
        if (! $saleReturn instanceof SaleReturn || ! $saleReturnItem instanceof SaleReturnItem) {
            return null;
        }

        return [
            'id' => $saleReturn->id,
            'offline_id' => $saleReturn->offline_sale_return_id,
            'location_id' => $saleReturn->counterUpdate?->counter?->location?->id,
            'location_name' => $saleReturn->counterUpdate?->counter?->location?->name,
            'happened_at' => $saleReturn->happened_at,
            'promoter_details' => $saleReturnItem->saleItem ? $saleReturnItem->saleItem->promoters->map(
                fn (Promoter $promoter): array => [
                    'id' => $promoter->employee?->id,
                    'name' => $promoter->employee?->getFullName(),
                    'staff_id' => $promoter->employee?->staff_id,
                ]
            ) : null,
            'member_info' => $saleReturn->member ? [
                'id' => $saleReturn->member->id,
                'first_name' => $saleReturn->member->first_name,
                'last_name' => $saleReturn->member->last_name,
                'email' => $saleReturn->member->email,
                'mobile_number' => $saleReturn->member->mobile_number,
                'card_number' => $saleReturn->member->card_number,
            ] : null,
        ];
    }
}
