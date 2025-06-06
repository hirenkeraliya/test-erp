<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\Resources;

use App\CommonFunctions;
use App\Models\City;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\SaleReturnReason;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

class OrderReturnReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $orderReturn = $this->resource;

        /** @var Collection $orderReturnItems */
        $orderReturnItems = $orderReturn->getOrderReturnItems();

        /** @var StoreManager $storeManager */
        $storeManager = $orderReturn->getStoreManager();

        /** @var Employee $storeManagerEmployee */
        $storeManagerEmployee = $storeManager->getEmployee();

        /** @var Location $location */
        $location = $orderReturn->getLocation();

        /** @var ?City $city */
        $city = $location->getCity();

        /** @var ?Member $member */
        $member = $orderReturn->getMember();

        /** @var Company $company */
        $company = $location->getCompany();

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $orderReturn->getCreatedAt());
        $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $orderReturn->getKey(),
            'receipt_number' => $orderReturn->getReceiptNumber(),
            'location' => [
                'id' => $location->getKey(),
                'name' => $location->getName(),
                'address_line_1' => $location->getAddressLine1(),
                'address_line_2' => $location->getAddressLine2(),
                'city' => $city?->name,
                'area_code' => $location->getAreaCode(),
                'receipt_footer' => $location->getReceiptFooter(),
                'disclaimer' => $location->getDisclaimer(),
            ],
            'company' => [
                'id' => $company->getKey(),
                'name' => $company->getName(),
                'social_security_number' => $company->getSocialSecurityNumber(),
                'logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            ],
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'member_id' => $member instanceof Member ? $member->getKey() : null,
            'gross_orders' => CommonFunctions::numberFormat($orderReturn->getGrossTotal()),
            'total_tax_amount' => $orderReturn->getTotalTaxAmount(),
            'total_discount_amount' => $orderReturn->getTotalDiscountAmount(),
            'total_amount_paid' => $orderReturn->getTotalPricePaid(),
            'units_returned' => $this->getTotalUnitsReturned($orderReturnItems),
            'order_return_items' => $this->getPreparedOrderReturnItems($orderReturnItems),
            'happened_at' => $happenedAt,
            'round_off' => $orderReturn->getRoundOffAmount(),
            'store_manager' => $storeManagerEmployee->getFullName(),
            'order_return_barcode' => $this->getOrderReturnBarcode($orderReturn->getReceiptNumber()),
        ];
    }

    private function getOrderReturnBarcode(string $receiptNumber): string
    {
        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);

        return base64_encode(
            $barcodeGeneratorPNG->getBarcode($receiptNumber, $barcodeGeneratorPNG::TYPE_CODE_128, 2, 35)
        );
    }

    private function getTotalUnitsReturned(Collection $orderReturnItems): float
    {
        $totalUnitsReturned = $orderReturnItems->sum(
            fn ($orderReturnItem): ?float => $orderReturnItem->getQuantity()
        );

        return CommonFunctions::numberFormat((float) $totalUnitsReturned);
    }

    private function getPreparedOrderReturnItems(Collection $orderReturnItems): Collection
    {
        return $orderReturnItems->map(function (OrderReturnItem $orderReturnItem): array {
            /** @var Product $product */
            $product = $orderReturnItem->getProduct();

            /** @var OrderItem $orderItem */
            $orderItem = $orderReturnItem->orderItem;

            /** @var SaleReturnReason $orderReturnReason */
            $orderReturnReason = $orderReturnItem->getOrderReturnReason();

            return [
                'id' => $orderReturnItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'upc' => $product->getUpc(),
                'quantity' => $orderReturnItem->getQuantity(),
                'unit_price' => $orderItem->getOriginalPricePerUnit(),
                'subtotal' => CommonFunctions::numberFormat(
                    $orderItem->getOriginalPricePerUnit() * $orderReturnItem->getQuantity()
                ),
                'total_discount_amount' => $orderReturnItem->getTotalDiscountAmount(),
                'total_tax_amount' => $orderReturnItem->getTotalTaxAmount(),
                'total_price_paid' => $orderReturnItem->getTotalPricePaid(),
                'order_return_reason' => $orderReturnReason->getReason(),
                'put_back_in_inventory' => $orderReturnReason->getPutBackInInventory(),
                'promoters' => $this->getPromoters($orderItem),
            ];
        });
    }

    private function getPromoters(OrderItem $orderItem): ?array
    {
        if ($orderItem->promoters->isEmpty()) {
            return null;
        }

        return $orderItem->promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ];
        })->toArray();
    }
}
