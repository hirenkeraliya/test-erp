<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosClosedCounterSaleResource extends JsonResource
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

        /** @var Collection $saleMismatches */
        $saleMismatches = $sale->mismatches;
        $messages = $saleMismatches->pluck('message')->toArray();

        /** @var Collection $saleItems */
        $saleItems = $sale->getSaleItems();

        /** @var Collection $salePayments */
        $salePayments = $sale->payments;

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        /** @var ?VoidSale $voidSale */
        $voidSale = $sale->voidSale;

        /** @var ?VoidSaleReason $voidSaleReason */
        $voidSaleReason = $voidSale instanceof VoidSale ? $voidSale->voidSaleReason : null;

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $sale->getKey(),
            'offline_sale_id' => $sale->offline_sale_id,
            'user_type' => $userDataPreparer->getUserType($sale),
            'user_id' => $sale->member_id,
            'user_details' => $userDataPreparer->getBasicUserDetails($sale->member),
            'member_id' => $sale->member_id,
            'member' => $userDataPreparer->getBasicUserDetails($sale->member),
            'cashier' => [
                'id' => $cashier->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
            ],
            'counter' => [
                'id' => $counter->id,
                'name' => $counter->getName(),
            ],
            'total_tax_amount' => (float) $sale->total_tax_amount,
            'cart_discount_amount' => (float) $sale->cart_discount_amount,
            'items_discount_amount' => (float) $sale->items_discount_amount,
            'total_discount_amount' => (float) $sale->total_discount_amount,
            'total_amount_paid' => (float) $sale->total_amount_paid,
            'change_due' => (float) $sale->change_due,
            'layaway_pending_amount' => (float) $sale->layaway_pending_amount,
            'credit_pending_amount' => (float) $sale->credit_pending_amount,
            'sale_items' => $this->getPreparedSaleItems($saleItems),
            'payments' => $salePayments->map(function ($payment): array {
                /** @var SalePayment $salePayment */
                $salePayment = $payment;

                /** @var PaymentType $paymentType */
                $paymentType = $salePayment->paymentType;

                return [
                    'id' => $salePayment->getKey(),
                    'payment_type' => $paymentType,
                    'amount' => (float) $salePayment->amount,
                    'happened_at' => $salePayment->happened_at,
                ];
            }),
            'status' => SaleStatus::getFormattedCaseName($sale->getStatus()),
            'sale_notes' => $sale->notes,
            'bill_reference_number' => $sale->bill_reference_number,
            'happened_at' => $sale->happened_at,
            'has_mismatch' => $sale->has_mismatch,
            'sale_mismatches' => $messages,
            'void_reason' => $voidSaleReason instanceof VoidSaleReason ? $voidSaleReason->reason : null,
        ];
    }

    private function getPreparedSaleItems(Collection $saleItems): Collection
    {
        return $saleItems->map(function ($item): array {
            /** @var SaleItem $saleItem */
            $saleItem = $item;

            /** @var Product $product */
            $product = $saleItem->product;

            return [
                'id' => $saleItem->getKey(),
                'product_id' => $saleItem->product_id,
                'product' => $product,
                'quantity' => (float) $saleItem->quantity,
                'returned_quantity' => (float) $saleItem->returned_quantity,
                'original_price_per_unit' => (float) $saleItem->original_price_per_unit,
                'cart_discount_amount' => (float) $saleItem->cart_discount_amount,
                'item_discount_amount' => (float) $saleItem->item_discount_amount,
                'total_discount_amount' => (float) $saleItem->total_discount_amount,
                'total_tax_amount' => (float) $saleItem->total_tax_amount,
                'price_paid_per_unit' => (float) $saleItem->price_paid_per_unit,
                'total_price_paid' => $saleItem->calculateFinalSaleItemAmount(),
                'promoters' => $this->getPromoters($saleItem),
            ];
        });
    }

    private function getPromoters(SaleItem $saleItem): ?array
    {
        if ($saleItem->promoters->isEmpty()) {
            return null;
        }

        return $saleItem->promoters->map(function (Promoter $promoter): array {
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
