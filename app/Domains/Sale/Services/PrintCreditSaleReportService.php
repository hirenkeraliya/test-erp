<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\Sale;
use App\Models\SaleDiscount;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PrintCreditSaleReportService
{
    public function print(int $saleId, int $companyId, ?int $locationId): string
    {
        $saleQueries = resolve(SaleQueries::class);
        $productService = resolve(ProductService::class);

        $creditSaleDetails = $saleQueries->getCreditSaleItemsByForPrint($saleId, $companyId, $locationId);

        $salesDetails = $this->preparedData($creditSaleDetails, $productService);
        [$company, $location] = $this->preparedCompanyAndStore($creditSaleDetails);

        return view('prints.credit_sale_report', [
            'salesDetails' => $salesDetails,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'location' => $location,
            'productVariant' => config('app.product_variant'),
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Sale $sale, ProductService $productService): array
    {
        /** @var Collection $saleItems */
        $saleItems = $sale->saleItems;

        /** @var Collection $saleDiscounts */
        $saleDiscounts = $sale->saleDiscounts;

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        /** @var Member|null $member */
        $member = $sale->member;

        $userDataPrepare = resolve(UserDataPreparer::class);

        return [
            'user_details' => $userDataPrepare->getMemberNameAndAddressDetails($member),
            'gross_sales' => CommonFunctions::numberFormat($sale->getGrossTotal()),
            'total_discount_amount' => $sale->getTotalDiscountAmount(),
            'total_tax_amount' => $sale->getTotalTaxAmount(),
            'total_amount_paid' => $sale->getTotalAmountPaid(),
            'credit_pending_amount' => $sale->getCreditPendingAmount(),
            'sale_items' => $this->getPreparedSaleItems($saleItems, $productService),
            'sale_discounts' => SaleDiscount::getPreparedSaleDiscounts($saleDiscounts),
            'payments' => SalePayment::getPreparedPayments($salePayments),
            'receipt_number' => $sale->getOfflineSaleId(),
        ];
    }

    private function preparedCompanyAndStore(Sale $sale): array
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        return [$company, $location];
    }

    private function getPreparedSaleItems(Collection $saleItems, ProductService $productService): array
    {
        return $saleItems->map(function ($saleItem) use ($productService): array {
            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

            /** @var Product $product */
            $product = $saleItem->product;

            return [
                'id' => $saleItem->getKey(),
                'product' => $product->getName(),
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'attributes' => $productService->getAttributesForPrint($product),
                'upc' => $product->getUpc(),
                'quantity' => $saleItem->getQuantity(),
                'unit_price' => $saleItem->getPricePaidPerUnit(),
                'subtotal' => CommonFunctions::numberFormat($saleItem->getSubTotal()),
                'total_discount_amount' => $saleItem->getTotalDiscountAmount(),
                'total_tax_amount' => $saleItem->getTotalTaxAmount(),
                'total_price_paid' => $saleItem->getTotalPricePaid(),
                'sale_item_discounts' => $this->getSaleItemDiscountDetails($saleItemDiscounts),
                'promoters' => Promoter::getPromoters($saleItem),
            ];
        })->toArray();
    }

    private function getSaleItemDiscountDetails(Collection $saleItemDiscounts): string
    {
        $text = '';

        foreach ($saleItemDiscounts as $saleItemDiscount) {
            if ($saleItemDiscount->getDiscountableType() === DiscountableTypes::PROMOTION->value) {
                $text = 'Promotion: ' . $saleItemDiscount->getAmount() . "\n";
            }

            if ($saleItemDiscount->getDiscountableType() === DiscountableTypes::DREAM_PRICE->value) {
                $text .= 'Dream Price: ' . $saleItemDiscount->getAmount() . "\n";
            }

            if ($saleItemDiscount->getDiscountableType() === DiscountableTypes::COMPLIMENTARY_ITEM_REASON->value) {
                $text .= 'Complimentary Item: ' . $saleItemDiscount->getAmount();
            }
        }

        return $text;
    }
}
