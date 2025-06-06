<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\SaleReturnInventoryService;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Models\Cashier;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use Illuminate\Support\Collection;

class SaveSaleReturnDetailsService
{
    public function saveSaleReturnDetails(
        Cashier $cashier,
        CheckSaleDetailsService $checkSaleDetailsService,
        ?int $memberId
    ): ?SaleReturn {
        $saleReturnService = $checkSaleDetailsService->saleReturnService;
        if (! $saleReturnService->hasReturnItems()) {
            return null;
        }

        $sale = $saleReturnService->returnedSaleItems->first()->sale;

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $saleReturnService->checkRoundOffValue();
        $location = $checkSaleDetailsService->location;
        $digitalInvoiceNumber = $this->getSequenceNumber($location, SequenceTypes::SR);

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturn = $saleReturnQueries->addNew(
            $memberId,
            $counterUpdateId,
            $saleReturnService->returnedSaleItems->first()->sale_id,
            $checkSaleDetailsService->saleData,
            $saleReturnService->saleReturnMismatches->isNotEmpty(),
            $digitalInvoiceNumber,
        );

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        foreach ($saleReturnService->returnItems as $returnItem) {
            $returnedSaleItem = $saleReturnService->returnedSaleItems->firstWhere('id', $returnItem['sale_item_id']);

            $saleItemQueries->incrementReturnedQuantity($returnedSaleItem, (float) $returnItem['quantity']);

            $saleReturnService->returnedSaleItems = $saleReturnService->getReturnedSaleItems(
                $saleReturnService->returnItems->pluck('sale_item_id')->unique()->toArray()
            );

            foreach ($returnItem['sale_return_details'] as $returnItemDetails) {
                $totalDiscountAmount = 0;
                $itemDiscountAmount = 0;
                $itemCartDiscount = 0;
                $totalTaxAmount = 0;

                if ($returnedSaleItem->cart_discount_amount) {
                    $itemCartDiscount = $this->getReturnItemAmountFor(
                        (float) $returnedSaleItem->cart_discount_amount,
                        (float) $returnedSaleItem->quantity,
                        (float) $returnItemDetails['quantity']
                    );
                }

                if ($returnedSaleItem->item_discount_amount) {
                    $itemDiscountAmount = $this->getReturnItemAmountFor(
                        (float) $returnedSaleItem->item_discount_amount,
                        (float) $returnedSaleItem->quantity,
                        (float) $returnItemDetails['quantity']
                    );
                }

                if ($returnedSaleItem->total_discount_amount) {
                    $totalDiscountAmount = $this->getReturnItemAmountFor(
                        (float) $returnedSaleItem->total_discount_amount,
                        (float) $returnedSaleItem->quantity,
                        (float) $returnItemDetails['quantity']
                    );
                }

                if ($returnedSaleItem->total_tax_amount) {
                    $totalTaxAmount = $this->getReturnItemAmountFor(
                        (float) $returnedSaleItem->total_tax_amount,
                        (float) $returnedSaleItem->quantity,
                        (float) $returnItemDetails['quantity']
                    );
                }

                $totalPricePaid = $returnItem['price_paid_per_unit'] * $returnItem['quantity'];

                $saleReturnReason = $saleReturnService->saleReturnReasons->firstWhere(
                    'id',
                    $returnItemDetails['sale_return_reason_id']
                );

                $saleReturnItem = $saleReturnItemQueries->addNew(
                    $saleReturnReason->id,
                    $saleReturn->id,
                    $returnedSaleItem->id,
                    $returnedSaleItem->product_id,
                    (float) $returnItemDetails['quantity'],
                    $totalPricePaid,
                    $totalTaxAmount,
                    $itemCartDiscount,
                    $itemDiscountAmount,
                    $totalDiscountAmount
                );

                if (! $returnedSaleItem->product->is_non_inventory) {
                    $this->updateInventory(
                        $checkSaleDetailsService,
                        $returnedSaleItem,
                        $saleReturnItem,
                        $cashier,
                        $saleReturnReason,
                        $returnItemDetails,
                    );
                }

                if ($saleReturnService->isProductBeingExchanged($returnedSaleItem->id)) {
                    continue;
                }

                $this->revertUsedLoyaltyPoints(
                    $returnedSaleItem->id,
                    ModelMapping::SALE_ITEM->name,
                    $sale->member,
                    $saleReturnItem,
                    $checkSaleDetailsService->saleData->happened_at,
                );
            }
        }

        $saleReturnRoundOff = $checkSaleDetailsService->saleData->sale_return_round_off_amount;

        if (null === $saleReturnRoundOff) {
            $saleReturnRoundOff = $this->calculateSaleReturnRoundOff($saleReturn);
        }

        $saleReturnQueries->updateTotals($saleReturn, $saleReturnRoundOff);
        $location = $checkSaleDetailsService->location;
        $digitalInvoiceNumber = $this->getSequenceNumber($location, SequenceTypes::CN);

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNoteQueries->addNew(
            $counterUpdateId,
            $saleReturn->id,
            $digitalInvoiceNumber,
            $saleReturn->total_price_paid,
            $checkSaleDetailsService->saleData->happened_at,
            $checkSaleDetailsService->location->credit_note_expiration_days,
            $memberId,
        );

        $this->decreaseLoyaltyPoints($saleReturn, $saleReturnService);

        $this->saveSaleReturnMismatches($saleReturn, $saleReturnService);

        return $saleReturnQueries->loadRelations($saleReturn);
    }

    public function revertUsedLoyaltyPoints(
        int $affectedById,
        string $affectedByType,
        ?Member $member,
        SaleReturnItem $saleReturnItem,
        string $happenedAt,
    ): void {
        if (! $member instanceof Member) {
            return;
        }

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdates = $loyaltyPointUpdateQueries->getUsedLoyaltyPoint(
            $affectedById,
            $affectedByType,
            LoyaltyPointUpdateTypes::USED->value
        );

        if ($loyaltyPointUpdates->isEmpty()) {
            return;
        }

        foreach ($loyaltyPointUpdates as $loyaltyPointUpdate) {
            $expiryDate = null;
            if ($loyaltyPointUpdate->loyaltyPoint) {
                $expiryDate = $loyaltyPointUpdate->loyaltyPoint->expiry_date;
            }

            $revertLoyaltyPointService = resolve(RevertLoyaltyPointService::class);
            $revertLoyaltyPointService->increaseLoyaltyPoints(
                $member,
                $saleReturnItem,
                (int) abs($loyaltyPointUpdate->points),
                $happenedAt,
                $expiryDate
            );
        }
    }

    public function calculateSaleReturnRoundOff(SaleReturn $saleReturn): float
    {
        $saleReturnAmount = $saleReturn->saleReturnItems->sum('total_price_paid');

        return RoundOffConfiguration::roundOffCalculationFor((string) $saleReturnAmount);
    }

    public function getReturnItemAmountFor(float $totalAmount, float $itemQuantity, float $returnQuantity): float
    {
        $totalAmount = CommonFunctions::numberFormat($totalAmount / $itemQuantity);

        return CommonFunctions::numberFormat($totalAmount * $returnQuantity);
    }

    public function updateInventory(
        CheckSaleDetailsService $checkSaleDetailsService,
        SaleItem $saleItem,
        SaleReturnItem $saleReturnItem,
        Cashier $cashier,
        SaleReturnReason $saleReturnReason,
        array $returnItemDetails
    ): void {
        if (
            $saleItem->product &&
            $saleItem->product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value
        ) {
            $this->updateAssemblyProductInventory(
                $checkSaleDetailsService,
                $saleItem,
                $saleReturnItem,
                $cashier,
                $saleReturnReason,
                $returnItemDetails
            );

            return;
        }

        $saleItemUnits = $this->getSaleItemUnits(
            $saleItem,
            $checkSaleDetailsService->saleReturnService,
            $returnItemDetails
        );

        $productBoxUnits = $saleItem->product_box_units > 0 ? $saleItem->product_box_units : 1;

        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $saleReturnInventoryService = resolve(SaleReturnInventoryService::class);
        $quantitiesToBeHandled = abs((float) $returnItemDetails['quantity'] * $productBoxUnits);
        $happenedAt = $checkSaleDetailsService->saleData->happened_at;
        foreach ($saleItemUnits as $saleItemUnit) {
            if ($quantitiesToBeHandled <= 0) {
                return;
            }

            $availableQuantitiesInThisRecord = $saleItemUnit['quantity'] - $saleItemUnit['returned_quantity'];

            $quantitiesToReturnFromThisRecord = $availableQuantitiesInThisRecord;

            if ($availableQuantitiesInThisRecord >= $quantitiesToBeHandled) {
                $quantitiesToReturnFromThisRecord = $quantitiesToBeHandled;
                $quantitiesToBeHandled = 0;
            }

            $quantitiesToBeHandled -= $quantitiesToReturnFromThisRecord;

            $saleItemUnitQueries->incrementReturnedQuantity($saleItemUnit, (float) $quantitiesToReturnFromThisRecord);

            $locationId = $checkSaleDetailsService->location->id;

            if (
                ! $saleReturnReason->put_back_in_inventory
                && $saleReturnReason->location_id
            ) {
                $locationId = $saleReturnReason->location_id;
            }

            $saleReturnInventoryService->addInventory(
                $saleReturnItem,
                $cashier,
                (float) $quantitiesToReturnFromThisRecord,
                $locationId,
                $saleItem->product_id,
                $saleItemUnit->purchase_amount_id,
                $saleItemUnit->batch_id,
                $happenedAt,
                $saleItemUnit->serial_number_id
            );
        }
    }

    public function updateAssemblyProductInventory(
        CheckSaleDetailsService $checkSaleDetailsService,
        SaleItem $saleItem,
        SaleReturnItem $saleReturnItem,
        Cashier $cashier,
        SaleReturnReason $saleReturnReason,
        array $returnItemDetails
    ): void {
        $assemblyProductSaleItemUnits = $this->getSaleItemUnits(
            $saleItem,
            $checkSaleDetailsService->saleReturnService,
            $returnItemDetails
        );

        $happenedAt = $checkSaleDetailsService->saleData->happened_at;

        foreach ($saleItem->saleItemAssemblyChildProducts as $saleItemAssemblyChildProduct) {
            $locationId = $checkSaleDetailsService->location->id;

            $inventoryQueries = resolve(InventoryQueries::class);
            $inventory = $inventoryQueries->fetchOrCreate(
                $locationId,
                $saleItemAssemblyChildProduct->child_product_id
            );

            $saleItemUnits = $assemblyProductSaleItemUnits->where('inventory_id', $inventory->id);

            $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
            $saleReturnInventoryService = resolve(SaleReturnInventoryService::class);
            $quantitiesToBeHandled = abs(
                (float) $returnItemDetails['quantity'] * $saleItemAssemblyChildProduct->units
            );
            foreach ($saleItemUnits as $saleItemUnit) {
                if ($quantitiesToBeHandled <= 0) {
                    return;
                }

                $availableQuantitiesInThisRecord = $saleItemUnit['quantity'] - $saleItemUnit['returned_quantity'];

                $quantitiesToReturnFromThisRecord = $availableQuantitiesInThisRecord;

                if ($availableQuantitiesInThisRecord >= $quantitiesToBeHandled) {
                    $quantitiesToReturnFromThisRecord = $quantitiesToBeHandled;
                    $quantitiesToBeHandled = 0;
                }

                $quantitiesToBeHandled -= $quantitiesToReturnFromThisRecord;

                $saleItemUnitQueries->incrementReturnedQuantity(
                    $saleItemUnit,
                    (float) $quantitiesToReturnFromThisRecord
                );

                if (
                    ! $saleReturnReason->put_back_in_inventory
                    && $saleReturnReason->location_id
                ) {
                    $locationId = $saleReturnReason->location_id;
                }

                $saleReturnInventoryService->addInventory(
                    $saleReturnItem,
                    $cashier,
                    (float) $quantitiesToReturnFromThisRecord,
                    $locationId,
                    $saleItemAssemblyChildProduct->child_product_id,
                    $saleItemUnit->purchase_amount_id,
                    $saleItemUnit->batch_id,
                    $happenedAt
                );
            }
        }
    }

    public function saveSaleReturnMismatches(SaleReturn $saleReturn, SaleReturnService $saleReturnService): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($saleReturnService->saleReturnMismatches as $saleReturnMismatch) {
            $posMismatchQueries->addNew($saleReturn, $saleReturnMismatch);
        }
    }

    public function getSaleItemUnits(
        SaleItem $saleItem,
        SaleReturnService $saleReturnService,
        array $returnItemDetails
    ): Collection {
        if (array_key_exists('serial_number', $returnItemDetails) && $returnItemDetails['serial_number']) {
            $serialNumberQueries = resolve(SerialNumberQueries::class);
            $serialNumber = $serialNumberQueries->getBySerialNumber(
                $saleItem->product_id,
                $returnItemDetails['serial_number']
            );

            $serialNumberQueries->updateStatus($serialNumber, SerialNumberStatus::ACTIVE->value);

            return $saleItem->saleItemUnits->where('serial_number_id', $serialNumber->id);
        }

        /** @var Product $product */
        $product = $saleItem->product;
        if ($product->has_batch) {
            $batch = $saleReturnService->batches
                ->where('product_id', $saleItem->product_id)
                ->firstWhere('number', $returnItemDetails['batch_number']);

            return $saleItem->saleItemUnits->where('batch_id', $batch->id);
        }

        return $saleItem->saleItemUnits;
    }

    public function decreaseLoyaltyPoints(SaleReturn $saleReturn, SaleReturnService $saleReturnService): void
    {
        $sale = $saleReturnService->returnedSaleItems->first()->sale;
        if ($saleReturnService->areAllOfTheReturnItemsBeingExchanged()) {
            return;
        }

        if (null === $saleReturn->member_id) {
            return;
        }

        $returnLoyaltyPoints = 0;
        foreach ($sale->issuedLoyaltyPoints as $loyaltyPoint) {
            $totalAmountPaid = $this->getTotalAmountPaidExcludedBrands($sale, $loyaltyPoint->loyaltyCampaign);
            if ($loyaltyPoint->minimum_spend_amount > $totalAmountPaid) {
                $returnLoyaltyPoints += $loyaltyPoint->points;
            }
        }

        if ($returnLoyaltyPoints <= 0) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $sale->member,
            $returnLoyaltyPoints,
            LoyaltyPointUpdateTypes::SALE_RETURN->value,
            $saleReturn->id,
            ModelMapping::SALE_RETURN->name,
            $saleReturn->happened_at
        );
    }

    public function getItemAmountPaid(SaleItem $saleItem): float
    {
        return CommonFunctions::numberFormat(
            ($saleItem->quantity - $saleItem->returned_quantity) * $saleItem->price_paid_per_unit
        );
    }

    public function getTotalAmountPaid(Sale $sale): float
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadSaleItems($sale);
        $totalAmountPaid = $sale->saleItems->sum(fn ($saleItem): float => $this->getItemAmountPaid($saleItem));

        return CommonFunctions::numberFormat($totalAmountPaid);
    }

    public function getTotalAmountPaidExcludedBrands(Sale $sale, LoyaltyCampaign $loyaltyCampaign): float
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadSaleItemsProductAndBrand($sale);
        $totalAmountPaidExcludeByBrands = 0.0;
        foreach ($sale->saleItems as $saleItem) {
            if ($this->checkExcludedBrands($saleItem, $loyaltyCampaign)) {
                continue;
            }

            $totalAmountPaidExcludeByBrands += CommonFunctions::numberFormat(
                ($saleItem->quantity - $saleItem->returned_quantity) * $saleItem->price_paid_per_unit
            );
        }

        return $totalAmountPaidExcludeByBrands;
    }

    public function useCreditNote(
        CheckSaleDetailsService $checkSaleDetailsService,
        Sale $sale,
        SaleReturn $saleReturn,
        int $counterUpdateId
    ): void {
        if (! $saleReturn->creditNote) {
            return;
        }

        $saleData = $checkSaleDetailsService->saleData;
        $amountToBePaid = $sale->total_amount_paid - collect($saleData->payments)->sum('amount');

        $amount = $saleReturn->creditNote->available_amount;
        if ($saleReturn->creditNote->available_amount >= $amountToBePaid) {
            $amount = $amountToBePaid;
        }

        $payment = [];
        $payment['type_id'] = StaticPaymentTypes::CREDIT_NOTE->value;
        $payment['credit_note_id'] = $saleReturn->creditNote->id;
        $payment['amount'] = $amount;
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $salePaymentId = $salePaymentQueries->addNew($sale, $saleData->happened_at, $payment, $counterUpdateId);

        $creditNote = $saleReturn->creditNote;
        $paymentAmount = (float) $payment['amount'];

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNoteQueries->decreaseAvailableAmountAndMarkAsUsed($creditNote, $paymentAmount);

        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $creditNoteUseQueries->addNew($creditNote, $salePaymentId, $counterUpdateId, $paymentAmount);
    }

    public function getSequenceNumber(Location $location, SequenceTypes $sequenceType): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, $sequenceType->value)->number;

        return $location->code.'-'.$sequenceType->name.'-'.$number;
    }

    private function checkExcludedBrands(SaleItem $saleItem, LoyaltyCampaign $loyaltyCampaign): bool
    {
        if (! $saleItem->product) {
            return false;
        }

        if (! $saleItem->product->brand) {
            return false;
        }

        if (! $saleItem->product->brand->id) {
            return false;
        }

        return $loyaltyCampaign->excludedBrands->where('id', $saleItem->product->brand->id)->isNotEmpty();
    }
}
