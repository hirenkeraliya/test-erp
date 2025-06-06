<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\Enums\GiftCardTransactionTypes;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\SaleInventoryService;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\MembershipAssignment\MembershipAssignmentQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\ReservedStock\Services\SaleReservedStockService;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemAssemblyChildProduct\SaleItemAssemblyChildProductQueries;
use App\Domains\SaleItemComplimentary\SaleItemComplimentaryQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemPriceOverride\SaleItemPriceOverrideQueries;
use App\Domains\SaleLoyaltyPoint\SaleLoyaltyPointQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SalePriceOverride\SalePriceOverrideQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Cashier;
use App\Models\Category;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\HappyHourDiscount;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SaveSaleDetailsService
{
    public function saveDetails(
        Cashier $cashier,
        CheckSaleDetailsService $checkSaleDetailsService,
        ?int $memberId,
        ?SaleReturn $saleReturn = null
    ): ?Sale {
        if (! $checkSaleDetailsService->hasCartItems()) {
            return null;
        }

        $saleQueries = resolve(SaleQueries::class);
        $saleReservedStockService = resolve(SaleReservedStockService::class);

        /** @var int $counterUpdateId */
        $counterUpdateId = $cashier->getCounterUpdateId();

        $location = $checkSaleDetailsService->location;
        $digitalInvoiceNumber = $this->getSequenceNumber($location);

        $sale = $saleQueries->addNew(
            $memberId,
            $counterUpdateId,
            $checkSaleDetailsService->saleData,
            $digitalInvoiceNumber,
            $checkSaleDetailsService->saleMismatches->isNotEmpty(),
            $saleReturn?->getKey(),
        );

        $cartSubtotal = $checkSaleDetailsService->getCartSubtotal();
        $cartSubtotal -= $checkSaleDetailsService->saleDiscountService->getTotalItemDiscountAmount();

        $cartDiscount = $checkSaleDetailsService->saleDiscountService->getCartDiscountAmountFor($cartSubtotal);

        $isCartDiscountAmountSpecified = null !== $checkSaleDetailsService->saleData->cart_discount_amount;
        $cartDiscountAmountSpecified = (float) $checkSaleDetailsService->saleData->cart_discount_amount;
        $cartDiscountAmountCalculated = $cartDiscount['total_discount'];

        $cartDiscountAmount = $isCartDiscountAmountSpecified
            ? $cartDiscountAmountSpecified
            : $cartDiscountAmountCalculated;

        $cartSubtotalAfterDiscount = $cartSubtotal - $cartDiscountAmount;

        $totalTax = (float) $checkSaleDetailsService->saleData->total_tax_amount;

        if (null === $checkSaleDetailsService->saleData->total_tax_amount) {
            $totalTax = $checkSaleDetailsService->saleTaxService->getTotalTaxAmountFor($cartSubtotalAfterDiscount);
        }

        $sale = $saleQueries->loadRelations($sale);

        $productService = resolve(ProductService::class);
        $vendorCommissionPercentages = $productService->getVendorCommissionPercentages(
            $checkSaleDetailsService->products
        );

        foreach ($checkSaleDetailsService->cartItems as $cartItem) {
            $cartItem['vendor_commission_percentage'] = $vendorCommissionPercentages[$cartItem['id']];

            $itemDiscounts = $checkSaleDetailsService->saleDiscountService->getItemDiscountAmountFor($cartItem);
            $itemTotalDiscount = $itemDiscounts['total_discount'];

            $itemSubTotal = $checkSaleDetailsService->getItemSubtotal($cartItem);
            $itemSubTotalAfterDiscount = $itemSubTotal - $itemTotalDiscount;

            $itemCartDiscount = $checkSaleDetailsService->saleDiscountService->getItemCartDiscountAmount(
                $cartSubtotal,
                $itemSubTotalAfterDiscount,
                $cartItem
            );

            $itemSubTotalAfterDiscount -= $itemCartDiscount;

            $itemTax = $checkSaleDetailsService->saleTaxService->getItemTaxAmountFor(
                $itemSubTotalAfterDiscount,
                $totalTax,
                $cartSubtotalAfterDiscount
            );

            $exchangeReturnItemId = $this->getExchangeReturnItemId(
                $checkSaleDetailsService,
                $cartItem,
                $saleReturn
            );

            // When Product Loyalty Points discount apply in frontend then remove it
            if (
                $checkSaleDetailsService->hasProductLoyaltyPoints($cartItem)
                && ! $checkSaleDetailsService->isPriceAttached($cartItem)
            ) {
                $cartItem['price'] = 0;
            }

            $saleItemQueries = resolve(SaleItemQueries::class);
            $saleItem = $saleItemQueries->addNew(
                $sale,
                $cartItem,
                $itemSubTotal,
                $itemTax,
                $itemCartDiscount,
                (float) $itemTotalDiscount,
                $exchangeReturnItemId
            );

            if ($exchangeReturnItemId) {
                $saleItemExchangeService = resolve(SaleItemExchangeService::class);
                $saleItemExchangeService->saveSaleItemAndReturnItemDetails($saleItem, $exchangeReturnItemId);
            }

            $this->updateBoxProduct($checkSaleDetailsService, $saleItem, $cartItem);

            $this->saveSaleItemAssemblyChildProduct($checkSaleDetailsService, $saleItem->id, $cartItem);

            $this->saveItemPriceOverride($checkSaleDetailsService, $saleItem->id, $cartItem, $itemDiscounts);

            $this->saveSaleItemComplimentary($checkSaleDetailsService, $saleItem->id, $cartItem);

            $this->saveItemDiscounts($checkSaleDetailsService, $cartItem, $saleItem->id, $itemDiscounts);

            if ($checkSaleDetailsService->hasProductLoyaltyPoints($cartItem)) {
                $this->useItemLoyaltyPoints($checkSaleDetailsService, $saleItem, $sale->member, $cartItem);
            }

            if ($this->isAssemblyProduct($checkSaleDetailsService, $cartItem)) {
                continue;
            }

            if ($checkSaleDetailsService->isLayawaySale()) {
                $saleReservedStockService->updateReservedStock($saleItem, $cartItem, $checkSaleDetailsService);

                continue;
            }

            $this->updateInventory($saleItem, $cartItem, $cashier, $checkSaleDetailsService);
        }

        $this->saveCartWideDiscount($checkSaleDetailsService, $cartDiscount['cart_wide_discount'], $sale->id);

        $this->saveCartWideLoyaltyPointsDiscount(
            $checkSaleDetailsService,
            $cartDiscount['cart_wide_loyalty_point_discount'],
            $sale
        );

        $this->saveVoucherDiscount($checkSaleDetailsService, $cartDiscount['voucher_discount'], $sale->id);

        $this->saveCartPriceOverride($checkSaleDetailsService, $cartDiscount['price_override_discount'], $sale->id);

        $roundOffAmount = $checkSaleDetailsService->saleData->sale_round_off_amount;

        $sale = $saleQueries->loadRelations($sale);
        $this->updateLayawayDetails($sale, $checkSaleDetailsService, $roundOffAmount);
        $this->updateCreditDetails($sale, $checkSaleDetailsService, $roundOffAmount);

        $saleQueries->updateTotals($sale, $roundOffAmount);

        $sale = $saleQueries->loadRelations($sale);
        $this->savePayments($checkSaleDetailsService, $sale, $saleReturn, $counterUpdateId);

        if ($memberId) {
            $memberQueries = resolve(MemberQueries::class);
            $memberQueries->updateLastPurchaseDate($checkSaleDetailsService->companyId, $memberId);
            $this->updateMemberSalesQuantity($checkSaleDetailsService->cartItems->sum('quantity'), $memberId);
        }

        $sale = $saleQueries->loadRelations($sale);
        $checkSaleDetailsService->generateLoyaltyPointsService->saveGenerateLoyaltyPoints(
            $checkSaleDetailsService,
            $sale,
            $memberId
        );

        if (! $checkSaleDetailsService->isLayawaySale() && ! $checkSaleDetailsService->isCreditSale()) {
            $checkSaleDetailsService->generateVoucherService->saveVouchers($sale);
        }

        $this->updateSpentTillNow($sale);

        $this->updateMembership($sale, $checkSaleDetailsService->companyId);

        if (
            $checkSaleDetailsService->hasCashback()
            && ! $checkSaleDetailsService->isLayawaySale()
            && ! $checkSaleDetailsService->isCreditSale()
        ) {
            $checkSaleDetailsService->saleCashbackService->saveCashback($sale, $counterUpdateId);
        }

        $this->saveSaleMismatches($sale, $checkSaleDetailsService);

        return $sale;
    }

    public function getSequenceNumber(Location $location): string
    {
        $sequenceQueries = resolve(SequenceQueries::class);
        $number = $sequenceQueries->addNew($location->id, SequenceTypes::SS->value)->number;

        return $location->code.'-'.SequenceTypes::SS->name.'-'.$number;
    }

    public function isAssemblyProduct(CheckSaleDetailsService $checkSaleDetailsService, array $cartItem): bool
    {
        /** @var Product $product */
        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            return false;
        }

        return $product->is_non_inventory;
    }

    public function updateBoxProduct(
        CheckSaleDetailsService $checkSaleDetailsService,
        SaleItem $saleItem,
        array $cartItem
    ): void {
        if (! $checkSaleDetailsService->isBoxProductAttached($cartItem)) {
            return;
        }

        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
        $cartItemBoxProductId = $cartItem['box_product_id'] ?? $cartItem['product_bundle_id'];
        $productBox = $product->boxes->firstWhere('id', $cartItemBoxProductId);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItemQueries->updateBoxProductDetails($saleItem, $productBox);
    }

    public function updateInventory(
        SaleItem $saleItem,
        array $item,
        Cashier $cashier,
        CheckSaleDetailsService $checkSaleDetailsService
    ): void {
        $serialNumber = null;
        $product = $checkSaleDetailsService->products->firstWhere('id', $item['id']);

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $this->updateAssemblyProductInventory($saleItem, $item, $cashier, $checkSaleDetailsService, $product);

            return;
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate(
            $checkSaleDetailsService->location->getKey(),
            (int) $item['id']
        );

        $productBoxUnits = $saleItem->product_box_units > 0 ? $saleItem->product_box_units : 1;
        $itemQuantity = CommonFunctions::numberFormat($item['quantity'] * $productBoxUnits);

        $saleInventoryService = resolve(SaleInventoryService::class);

        if (! $product->has_batch) {
            if ($product->type_id === ProductTypes::SERIAL_PRODUCT->value && $checkSaleDetailsService->hasSerialNumberDetails(
                $item
            )) {
                $serialNumberQueries = resolve(SerialNumberQueries::class);

                foreach ($item['serial_number_details'] as $serialNumberDetail) {
                    $serialNumber = $serialNumberQueries->firstOrCreate(
                        $product->id,
                        $checkSaleDetailsService->companyId,
                        $serialNumberDetail['serial_number']
                    );

                    $serialNumberQueries->updateStatus($serialNumber, SerialNumberStatus::SOLD->value);

                    $saleInventoryService->updateInventoryUnits(
                        $inventory,
                        $product,
                        $checkSaleDetailsService->location->getKey(),
                        $saleItem,
                        $cashier,
                        (float) ('-' . 1),
                        $checkSaleDetailsService->saleData->happened_at,
                        null,
                        $serialNumber->id,
                    );
                }
            } else {
                $saleInventoryService->updateInventoryUnits(
                    $inventory,
                    $product,
                    $checkSaleDetailsService->location->getKey(),
                    $saleItem,
                    $cashier,
                    (float) ('-' . $itemQuantity),
                    $checkSaleDetailsService->saleData->happened_at,
                    null,
                    null,
                );
            }
        }

        if ($product->has_batch && $checkSaleDetailsService->hasBatchDetails($item)) {
            foreach ($item['batch_details'] as $batchDetail) {
                $batch = $checkSaleDetailsService->batches->where('product_id', $product->id)
                        ->firstWhere('number', $batchDetail['batch_number']);
                $batchId = $batch?->id;

                $batchQuantity = CommonFunctions::numberFormat($productBoxUnits * $batchDetail['quantity']);

                $saleInventoryService->updateInventoryUnits(
                    $inventory,
                    $product,
                    $checkSaleDetailsService->location->getKey(),
                    $saleItem,
                    $cashier,
                    (float) ('-' . $batchQuantity),
                    $checkSaleDetailsService->saleData->happened_at,
                    $batchId,
                    $serialNumber ? $serialNumber->id : null,
                );
            }
        }

        if ($inventory->stock < 0 || ($inventory->stock - $itemQuantity < 0)) {
            // Add Notification For Negative Inventory
        }

        $inventoryQueries->decreaseStock($inventory, $itemQuantity);
    }

    public function updateAssemblyProductInventory(
        SaleItem $saleItem,
        array $item,
        Cashier $cashier,
        CheckSaleDetailsService $checkSaleDetailsService,
        Product $assemblyProduct
    ): void {
        /** @var Collection $assemblyChildProducts */
        $assemblyChildProducts = $assemblyProduct->assemblyChildProducts;
        foreach ($assemblyChildProducts as $assemblyChildProduct) {
            /** @var Product $product */
            $product = $assemblyChildProduct->product;

            $inventoryQueries = resolve(InventoryQueries::class);
            $inventory = $inventoryQueries->fetchOrCreate(
                $checkSaleDetailsService->location->getKey(),
                $product->id
            );

            $itemQuantity = CommonFunctions::numberFormat($assemblyChildProduct->units * $item['quantity']);

            $saleInventoryService = resolve(SaleInventoryService::class);
            $saleInventoryService->updateInventoryUnits(
                $inventory,
                $product,
                $checkSaleDetailsService->location->getKey(),
                $saleItem,
                $cashier,
                (float) ('-' . $itemQuantity),
                $checkSaleDetailsService->saleData->happened_at,
                null
            );

            if ($inventory->stock < 0 || ($inventory->stock - $itemQuantity < 0)) {
                // Add Notification For Negative Inventory
            }

            $inventoryQueries->decreaseStock($inventory, $itemQuantity);
        }
    }

    public function updateLayawayDetails(
        Sale $sale,
        CheckSaleDetailsService $checkSaleDetailsService,
        ?float $roundOffAmount
    ): void {
        if ($checkSaleDetailsService->isLayawaySale()) {
            $payments = collect($checkSaleDetailsService->saleData->payments);

            $isLayawayPendingAmountSpecified = null !== $checkSaleDetailsService->saleData->layaway_pending_amount;
            $specifiedLayawayPendingAmount = (float) $checkSaleDetailsService->saleData->layaway_pending_amount;
            $calculatedLayawayPendingAmount = $sale->getSaleItems()->sum('total_price_paid') - $payments->sum(
                'amount'
            ) + $roundOffAmount;

            $layawayPendingAmount = $isLayawayPendingAmountSpecified
                                        ? $specifiedLayawayPendingAmount
                                        : $calculatedLayawayPendingAmount;

            $this->updateLayawayPaymentsToTheSaleItems($sale->getSaleItems(), $payments->sum('amount'));

            $saleQueries = resolve(SaleQueries::class);
            $saleQueries->updateLayawayPendingAmountAndStatus(
                $sale,
                $layawayPendingAmount,
                $checkSaleDetailsService->saleData->layaway_store_manager_id
            );

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::LAYAWAY_SALE->value,
                $sale->id,
                ModelMapping::SALE->name,
                $checkSaleDetailsService->saleData->layaway_store_manager_authorization_code
            );
        }
    }

    public function updateLayawayPaymentsToTheSaleItems(Collection $saleItems, float $totalPaidAmount): void
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $totalAmount = $saleItems->sum('total_price_paid');
        $saleItems = $saleItems->sortBy('total_price_paid')->values();
        $lastKey = $saleItems->keys()->last();
        $itemWiseTotalPaidAmount = 0;
        foreach ($saleItems as $saleItemKey => $saleItem) {
            $totalPricePaid = CommonFunctions::numberFormat(
                $totalPaidAmount * $saleItem->total_price_paid / $totalAmount
            );

            if ($saleItemKey === $lastKey) {
                $totalPricePaid = CommonFunctions::numberFormat($totalPaidAmount - $itemWiseTotalPaidAmount);
            }

            $itemWiseTotalPaidAmount += $totalPricePaid;

            $saleItemQueries->updateTotalPricePaid($saleItem, $totalPricePaid);
        }
    }

    public function updateCreditDetails(
        Sale $sale,
        CheckSaleDetailsService $checkSaleDetailsService,
        ?float $roundOffAmount
    ): void {
        if ($checkSaleDetailsService->isCreditSale()) {
            $payments = collect($checkSaleDetailsService->saleData->payments);

            $isCreditPendingAmountSpecified = null !== $checkSaleDetailsService->saleData->credit_pending_amount;
            $specifiedCreditPendingAmount = (float) $checkSaleDetailsService->saleData->credit_pending_amount;
            $calculatedCreditPendingAmount = $sale->getSaleItems()->sum('total_price_paid') - $payments->sum(
                'amount'
            ) + $roundOffAmount;

            $creditPendingAmount = $isCreditPendingAmountSpecified
                                        ? $specifiedCreditPendingAmount
                                        : $calculatedCreditPendingAmount;

            $this->updateCreditPaymentsToTheSaleItems($sale->getSaleItems(), $payments->sum('amount'));

            $saleQueries = resolve(SaleQueries::class);
            $saleQueries->updateCreditPendingAmountAndStatus(
                $sale,
                $creditPendingAmount,
                $checkSaleDetailsService->saleData->credit_store_manager_id
            );

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::CREDIT_SALE->value,
                $sale->id,
                ModelMapping::SALE->name,
                $checkSaleDetailsService->saleData->credit_store_manager_authorization_code
            );
        }
    }

    public function updateCreditPaymentsToTheSaleItems(Collection $saleItems, float $totalPaidAmount): void
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $totalAmount = $saleItems->sum('total_price_paid');
        $saleItems = $saleItems->sortBy('total_price_paid')->values();
        $lastKey = $saleItems->keys()->last();
        $itemWiseTotalPaidAmount = 0;
        foreach ($saleItems as $saleItemKey => $saleItem) {
            $totalPricePaid = CommonFunctions::numberFormat(
                $totalPaidAmount * $saleItem->total_price_paid / $totalAmount
            );

            if ($saleItemKey === $lastKey) {
                $totalPricePaid = CommonFunctions::numberFormat($totalPaidAmount - $itemWiseTotalPaidAmount);
            }

            $itemWiseTotalPaidAmount += $totalPricePaid;

            $saleItemQueries->updateTotalPricePaid($saleItem, $totalPricePaid);
        }
    }

    public function savePayments(
        CheckSaleDetailsService $checkSaleDetailsService,
        Sale $sale,
        ?SaleReturn $saleReturn,
        int $counterUpdateId
    ): void {
        $saleData = $checkSaleDetailsService->saleData;
        if ($saleReturn instanceof SaleReturn) {
            $saveSaleReturnDetailsService = resolve(SaveSaleReturnDetailsService::class);
            $saveSaleReturnDetailsService->useCreditNote(
                $checkSaleDetailsService,
                $sale,
                $saleReturn,
                $counterUpdateId
            );
        }

        if (! $saleData->payments) {
            return;
        }

        $salePaymentQueries = resolve(SalePaymentQueries::class);

        foreach ($saleData->payments as $payment) {
            $salePaymentId = $salePaymentQueries->addNew($sale, $saleData->happened_at, $payment);

            $this->useLoyaltyPoints($checkSaleDetailsService, $sale, $payment);
            $this->useBookingPayment($checkSaleDetailsService, $payment, $salePaymentId, $counterUpdateId);
            $this->useCreditNote($checkSaleDetailsService, $payment, $salePaymentId, $counterUpdateId);
            $this->useGiftCard($checkSaleDetailsService, $payment, $salePaymentId);
        }
    }

    public function saveSaleMismatches(Sale $sale, CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        foreach ($checkSaleDetailsService->saleMismatches as $saleMismatch) {
            $posMismatchQueries->addNew($sale, $saleMismatch);
        }
    }

    public function saveItemPriceOverride(
        CheckSaleDetailsService $checkSaleDetailsService,
        int $saleItemId,
        array $cartItem,
        array $itemDiscounts
    ): void {
        if (
            $checkSaleDetailsService->hasPriceOverride($cartItem) &&
            0.00 !== $itemDiscounts['price_override_discount']
        ) {
            $negotiatorId = null;
            $negotiatorType = '';

            if ($checkSaleDetailsService->hasStoreManagerPriceOverride($cartItem)) {
                $negotiatorId = $cartItem['store_manager_id'];
                $negotiatorType = ModelMapping::STORE_MANAGER->name;
            }

            if ($checkSaleDetailsService->hasDirectorPriceOverride($cartItem)) {
                $negotiatorId = $cartItem['director_id'];
                $negotiatorType = ModelMapping::DIRECTOR->name;
            }

            if ($checkSaleDetailsService->hasCashierPriceOverride($cartItem)) {
                $negotiatorId = $cartItem['cashier_id'];
                $negotiatorType = ModelMapping::CASHIER->name;
            }

            $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
            $saleItemPriceOverride = $saleItemPriceOverrideQueries->addNew(
                $saleItemId,
                (int) $negotiatorId,
                $negotiatorType,
                $itemDiscounts['price_override_discount']
            );

            $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
            $saleItemDiscountQueries->addNew(
                $saleItemId,
                (int) $saleItemPriceOverride->id,
                ModelMapping::SALE_ITEM_PRICE_OVERRIDE->name,
                $itemDiscounts['price_override_discount']
            );

            if (array_key_exists('store_manager_authorization_code', $cartItem)) {
                $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
                $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                    StoreManagerAuthorizationCodeUsageTypes::SALE_ITEM_PRICE_OVERRIDE->value,
                    (int) $saleItemPriceOverride->id,
                    ModelMapping::SALE_ITEM_PRICE_OVERRIDE->name,
                    $cartItem['store_manager_authorization_code']
                );
            }
        }
    }

    public function saveCartPriceOverride(
        CheckSaleDetailsService $checkSaleDetailsService,
        float $priceOverrideDiscount,
        int $saleId,
    ): void {
        if (
            $checkSaleDetailsService->hasPriceOverrideForCart() &&
            0.00 !== $priceOverrideDiscount
        ) {
            $negotiatorId = null;
            $negotiatorType = '';

            if ($checkSaleDetailsService->hasStoreManagerPriceOverrideForCart()) {
                $negotiatorId = $checkSaleDetailsService->saleData->store_manager_id;
                $negotiatorType = ModelMapping::STORE_MANAGER->name;
            }

            if ($checkSaleDetailsService->hasDirectorPriceOverrideForCart()) {
                $negotiatorId = $checkSaleDetailsService->saleData->director_id;
                $negotiatorType = ModelMapping::DIRECTOR->name;
            }

            if ($checkSaleDetailsService->hasCashierPriceOverrideForCart()) {
                $negotiatorId = $checkSaleDetailsService->saleData->cashier_id;
                $negotiatorType = ModelMapping::CASHIER->name;
            }

            $salePriceOverrideQueries = resolve(SalePriceOverrideQueries::class);
            $salePriceOverride = $salePriceOverrideQueries->addNew(
                $saleId,
                (int) $negotiatorId,
                $negotiatorType,
                $priceOverrideDiscount
            );

            $saleDiscountQueries = resolve(SaleDiscountQueries::class);
            $saleDiscountQueries->addNew(
                $saleId,
                (int) $salePriceOverride->id,
                ModelMapping::SALE_PRICE_OVERRIDE->name,
                $priceOverrideDiscount
            );

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::SALE_PRICE_OVERRIDE->value,
                $salePriceOverride->id,
                ModelMapping::SALE_PRICE_OVERRIDE->name,
                $checkSaleDetailsService->saleData->store_manager_authorization_code
            );
        }
    }

    public function saveSaleItemAssemblyChildProduct(
        CheckSaleDetailsService $checkSaleDetailsService,
        int $saleItemId,
        array $cartItem,
    ): void {
        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);

        if ($product->type_id !== ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        foreach ($product->assemblyChildProducts as $assemblyChildProduct) {
            $saleItemAssemblyChildProductQueries = resolve(SaleItemAssemblyChildProductQueries::class);
            $saleItemAssemblyChildProductQueries->addNew([
                'sale_item_id' => $saleItemId,
                'child_product_id' => $assemblyChildProduct->child_product_id,
                'units' => $assemblyChildProduct->units,
            ]);
        }
    }

    public function saveSaleItemComplimentary(
        CheckSaleDetailsService $checkSaleDetailsService,
        int $saleItemId,
        array $cartItem,
    ): void {
        if (! $checkSaleDetailsService->hasComplimentaryItem($cartItem)) {
            return;
        }

        $authorizerId = null;
        $authorizerType = '';

        if ($checkSaleDetailsService->hasStoreManager($cartItem)) {
            $authorizerId = $cartItem['store_manager_id'];
            $authorizerType = ModelMapping::STORE_MANAGER->name;
        }

        if ($checkSaleDetailsService->hasDirector($cartItem)) {
            $authorizerId = $cartItem['director_id'];
            $authorizerType = ModelMapping::DIRECTOR->name;
        }

        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemComplimentaryQueries->addNew(
            $saleItemId,
            (int) $authorizerId,
            $authorizerType,
            $checkSaleDetailsService->getItemSubtotal($cartItem),
        );
    }

    public function saveItemDiscounts(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem,
        int $saleItemId,
        array $itemDiscounts
    ): void {
        if (0.00 === $itemDiscounts['total_discount']) {
            return;
        }

        if (
            $checkSaleDetailsService->hasHappyHourDiscount($cartItem)
            && 0.00 !== $itemDiscounts['happy_hour_discount']
        ) {
            /** @var HappyHourDiscount $happyHourDiscount */
            $happyHourDiscount = $checkSaleDetailsService->saleDiscountService->happyHourDiscounts
                ->firstWhere('happyHourDiscountTransaction.offline_id', '===', $cartItem['happy_hours_offline_id']);

            $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
            $saleItemDiscountQueries->addNew(
                $saleItemId,
                $happyHourDiscount->id,
                ModelMapping::HAPPY_HOUR_DISCOUNT->name,
                $itemDiscounts['happy_hour_discount']
            );
        }

        if (
            $checkSaleDetailsService->hasComplimentaryItem($cartItem)
            && 0.00 !== $itemDiscounts['complimentary_item_discount']
        ) {
            $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
            $saleItemDiscountQueries->addNew(
                $saleItemId,
                (int) $cartItem['complimentary_item_reason_id'],
                ModelMapping::COMPLIMENTARY_ITEM_REASON->name,
                $itemDiscounts['complimentary_item_discount']
            );

            if (array_key_exists('store_manager_authorization_code', $cartItem)) {
                $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
                $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                    StoreManagerAuthorizationCodeUsageTypes::COMPLIMENTARY_ITEM->value,
                    (int) $cartItem['complimentary_item_reason_id'],
                    ModelMapping::COMPLIMENTARY_ITEM_REASON->name,
                    $cartItem['store_manager_authorization_code']
                );
            }
        }

        if (
            $checkSaleDetailsService->hasProductLoyaltyPoints($cartItem)
            && 0.00 !== $itemDiscounts['loyalty_point_item_discount']
        ) {
            $saleLoyaltyPointQueries = resolve(SaleLoyaltyPointQueries::class);
            $saleLoyaltyPoint = $saleLoyaltyPointQueries->addNew(
                (int) $cartItem['loyalty_points'],
                (float) $cartItem['loyalty_point_item_discount'],
                null,
                (int) $cartItem['id']
            );

            $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
            $saleItemDiscountQueries->addNew(
                $saleItemId,
                $saleLoyaltyPoint->id,
                ModelMapping::SALE_LOYALTY_POINT->name,
                $itemDiscounts['loyalty_point_item_discount']
            );
        }

        if (
            $checkSaleDetailsService->hasDreamPrice($cartItem)
            && 0.00 !== $itemDiscounts['dream_price_discount']
        ) {
            $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
            $saleItemDiscountQueries->addNew(
                $saleItemId,
                (int) $cartItem['dream_price_id'],
                ModelMapping::DREAM_PRICE->name,
                $itemDiscounts['dream_price_discount']
            );
        }

        if (! array_key_exists('promotion_id', $cartItem)) {
            return;
        }

        if (! $cartItem['promotion_id']) {
            return;
        }

        if (0.00 === $itemDiscounts['item_wise_discount']) {
            return;
        }

        $promoCode = null;

        if (array_key_exists('promo_code', $cartItem)) {
            $promoCode = $cartItem['promo_code'];
        }

        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemDiscountQueries->addNew(
            $saleItemId,
            (int) $cartItem['promotion_id'],
            ModelMapping::PROMOTION->name,
            $itemDiscounts['item_wise_discount'],
            $promoCode
        );
    }

    public function saveCartWideDiscount(
        CheckSaleDetailsService $checkSaleDetailsService,
        float $totalCartDiscount,
        int $saleId
    ): void {
        if ($checkSaleDetailsService->saleData->cart_promotion_id && 0.00 !== $totalCartDiscount) {
            $discountTypeClass = ModelMapping::PROMOTION->name;

            $saleDiscountQueries = resolve(SaleDiscountQueries::class);
            $saleDiscountQueries->addNew(
                $saleId,
                $checkSaleDetailsService->saleData->cart_promotion_id,
                $discountTypeClass,
                $totalCartDiscount,
                $checkSaleDetailsService->saleData->cart_promo_code,
            );
        }
    }

    public function saveCartWideLoyaltyPointsDiscount(
        CheckSaleDetailsService $checkSaleDetailsService,
        float $cartWideLoyaltyPointDiscount,
        Sale $sale
    ): void {
        if (! $checkSaleDetailsService->hasLoyaltyPointsForCart()) {
            return;
        }

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadRelations($sale);

        $member = $sale->member;
        if (! $member) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $member,
            (int) $checkSaleDetailsService->saleData->cart_loyalty_points,
            LoyaltyPointUpdateTypes::USED->value,
            $sale->getKey(),
            ModelMapping::SALE->name,
            $checkSaleDetailsService->saleData->happened_at
        );

        $saleLoyaltyPointQueries = resolve(SaleLoyaltyPointQueries::class);
        $saleLoyaltyPoint = $saleLoyaltyPointQueries->addNew(
            (int) $checkSaleDetailsService->saleData->cart_loyalty_points,
            $cartWideLoyaltyPointDiscount,
            $sale->id,
            null
        );

        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleDiscountQueries->addNew(
            $sale->id,
            $saleLoyaltyPoint->id,
            ModelMapping::SALE_LOYALTY_POINT->name,
            $cartWideLoyaltyPointDiscount,
        );
    }

    public function saveVoucherDiscount(
        CheckSaleDetailsService $checkSaleDetailsService,
        float $discountAmount,
        int $saleId
    ): void {
        if ($checkSaleDetailsService->saleData->voucher_number && 0.00 !== $discountAmount) {
            /** @var Voucher $voucher */
            $voucher = $checkSaleDetailsService->saleDiscountService->voucher;

            $voucherQueries = resolve(VoucherQueries::class);
            $voucherQueries->markAsUsed($voucher, $checkSaleDetailsService->saleData->happened_at);

            $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::USED->value,
                $checkSaleDetailsService->saleData->happened_at,
                $saleId,
                $checkSaleDetailsService->location->getKey()
            );

            $discountTypeClass = ModelMapping::VOUCHER->name;

            $saleDiscountQueries = resolve(SaleDiscountQueries::class);
            $saleDiscountQueries->addNew($saleId, $voucher->id, $discountTypeClass, $discountAmount);
        }
    }

    public function updateSpentTillNow(Sale $sale): void
    {
        if (null === $sale->member_id) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updateSpentTillNow((float) $sale->total_amount_paid, $sale->member_id);
    }

    public function updateMemberSalesQuantity(float $totalSaleQty, int $memberId): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $memberQueries->updateSalesQuantity($totalSaleQty, $memberId);
    }

    public function updateMembership(Sale $sale, int $companyId): void
    {
        if (null === $sale->member_id) {
            return;
        }

        $membershipQueries = resolve(MembershipQueries::class);
        $memberships = $membershipQueries->getByCompanyIdSortByMinimumSpendAmount($companyId);

        $this->updateMemberMembership($sale, $memberships);
    }

    public function updateMemberMembership(Sale $sale, Collection $memberships): void
    {
        if (null === $sale->member_id) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getByIdWithMembership($sale->member_id);
        $membership = $memberships->firstWhere('lifetime_value', '<', $member->spent_till_now);
        if (null === $membership) {
            return;
        }

        if ($member->membership && $member->membership->lifetime_value > $membership->lifetime_value) {
            return;
        }

        $memberQueries->setMembershipId($membership->id, $sale->member_id);

        $membershipAssignmentQueries = resolve(MembershipAssignmentQueries::class);
        $membershipAssignmentQueries->addNew($membership->id, $sale->member_id, $sale->happened_at);
    }

    public function useLoyaltyPoints(
        CheckSaleDetailsService $checkSaleDetailsService,
        Sale $sale,
        array $payment
    ): void {
        if (! $sale->member) {
            return;
        }

        if (! $checkSaleDetailsService->hasLoyaltyPoints($payment)) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $sale->member,
            (int) $payment['loyalty_points'],
            LoyaltyPointUpdateTypes::USED->value,
            $sale->getKey(),
            ModelMapping::SALE->name,
            $checkSaleDetailsService->saleData->happened_at
        );
    }

    public function useItemLoyaltyPoints(
        CheckSaleDetailsService $checkSaleDetailsService,
        SaleItem $saleItem,
        ?Member $member,
        array $cartItem
    ): void {
        if (! $member instanceof Member) {
            return;
        }

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $member,
            (int) $cartItem['loyalty_points'],
            LoyaltyPointUpdateTypes::USED->value,
            $saleItem->id,
            ModelMapping::SALE_ITEM->name,
            $checkSaleDetailsService->saleData->happened_at
        );
    }

    public function useCreditNote(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $payment,
        int $salePaymentId,
        int $counterUpdateId
    ): void {
        if (! array_key_exists('credit_note_id', $payment)) {
            return;
        }

        if (! $payment['credit_note_id']) {
            return;
        }

        $creditNotes = $checkSaleDetailsService->creditNotes;
        $creditNote = $creditNotes->firstWhere('id', $payment['credit_note_id']);
        $paymentAmount = (float) $payment['amount'];

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);

        $creditNoteQueries->decreaseAvailableAmountAndMarkAsUsed($creditNote, $paymentAmount);
        $creditNoteUseQueries->addNew($creditNote, $salePaymentId, $counterUpdateId, $paymentAmount);
    }

    public function useGiftCard(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $payment,
        int $salePaymentId,
    ): void {
        if (! array_key_exists('gift_card_id', $payment)) {
            return;
        }

        if (! $payment['gift_card_id']) {
            return;
        }

        $giftCards = $checkSaleDetailsService->giftCards;
        $giftCard = $giftCards->firstWhere('id', $payment['gift_card_id']);
        $paymentAmount = (float) $payment['amount'];

        $giftCardQueries = resolve(GiftCardQueries::class);
        $giftCardTransactionQueries = resolve(GiftCardTransactionQueries::class);

        $giftCardQueries->decreaseAvailableAmountAndMarkAsUsed($giftCard, $paymentAmount);

        $giftCardTransactionQueries->addNew(
            $giftCard,
            $salePaymentId,
            ModelMapping::SALE_PAYMENT->name,
            $paymentAmount,
            GiftCardTransactionTypes::USED->value
        );
    }

    public function useBookingPayment(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $payment,
        int $salePaymentId,
        int $counterUpdateId
    ): void {
        if (! array_key_exists('booking_payment_id', $payment)) {
            return;
        }

        if (! $payment['booking_payment_id']) {
            return;
        }

        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);

        $bookingPayments = $checkSaleDetailsService->bookingPayments;
        $bookingPayment = $bookingPayments->firstWhere('id', $payment['booking_payment_id']);
        $paymentAmount = (float) $payment['amount'];

        $bookingPaymentQueries->markAsUsed($bookingPayment, $paymentAmount);
        $bookingPaymentUseQueries->addNew($bookingPayment, $salePaymentId, $counterUpdateId, $paymentAmount);
    }

    public function getExchangeReturnItemId(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem,
        ?SaleReturn $saleReturn
    ): ?int {
        if (! $saleReturn instanceof SaleReturn) {
            return null;
        }

        if (! array_key_exists('is_exchange', $cartItem)) {
            return null;
        }

        if (! $cartItem['is_exchange']) {
            return null;
        }

        $saleReturnItem = $saleReturn->saleReturnItems->firstWhere('product_id', $cartItem['id']);
        if ($saleReturnItem instanceof SaleReturnItem) {
            return $saleReturnItem->getKey();
        }

        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
        if (! $product) {
            return null;
        }

        $saleReturnItem = $saleReturn->saleReturnItems->firstWhere('product.article_number', $product->article_number);

        if (null === $saleReturnItem) {
            return null;
        }

        return $saleReturnItem->getKey();
    }

    public function shareSaleDetailsThirdParty(Sale $sale): void
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Location $location */
        $location = $counter->getLocation();

        /** @var ?Member $member */
        $member = $sale->member;

        /** @var ?MemberAddress $memberAddress */
        $memberAddress = $member?->primaryMemberAddress;

        /** @var SaleItem $saleItem */
        $saleItem = $sale->getSaleItems()->first();

        /** @var Product $product */
        $product = $saleItem->product;

        /** @var ?Category $category */
        $category = $product->categories->first();

        $saleItemUnit = $saleItem->saleItemUnits->first();
        $serialNumber = null;
        if ($saleItemUnit && $saleItemUnit->serialNumber) {
            $serialNumber = $saleItemUnit->serialNumber->serial_number;
        }

        $expirationDate = null;

        if ($product->is_warranty) {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
            $expirationDate = $date->addMonths((int) $product->warranty_month)->format('Y-m-d H:i:s');
        }

        $data = [
            'customerCode' => $member?->code ?? null,
            'externalId' => $sale->offline_sale_id,
            'customerName' => $member?->getFullName() ?? null,
            'contactNumber' => $member?->mobile_number ?? null,
            'emailAddress' => $member?->email ?? null,
            'streetAddress' => $memberAddress?->address_line_1 ?? null,
            'city' => $memberAddress?->city ?? null,
            'postalCode' => $memberAddress?->area_code ?? null,
            'installationDate' => $sale->happened_at,
            'productCode' => $product->upc,
            'productName' => $product->name,
            'make' => $product->brand?->name,
            'model' => $product->article_number,
            'category' => $category?->name ?? null,
            'price' => $saleItem->total_price_paid,
            'warrantyType' => $product->is_warranty,
            'warrantyCode' => $serialNumber,
            'warrantyStartDate' => $sale->happened_at,
            'warrantyEndDate' => $expirationDate,
            'warrantyTerms' => $product->warranty_month ? 'warranty will be applicable for '. $product->warranty_month . ' months' : null,
            'promoter' => $saleItem->promoters->implode('employees.first_name', ', '),
            'store' => $location->name,
            'purchaseDate' => $sale->happened_at,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'key' => config('services.share_sale_details_to_third_party.share_sale_details_to_third_party_key'),
                'token' => config('services.share_sale_details_to_third_party.share_sale_details_to_third_party_token'),
            ])->timeout(config('services.http_time_out'))->post(
                config('services.share_sale_details_to_third_party.share_sale_details_to_third_party_url'),
                $data
            );

            Log::channel('pos_api')->info('sharing the sale details to third party', [
                'response' => $response,
            ]);
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'sharing the sale details to third party failed.');
        }
    }
}
