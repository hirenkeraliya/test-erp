<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\DataObjects\OrderData;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\StoreManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CheckOrderDetailsService
{
    public Company $company;

    public OrderData $orderData;

    public Collection $products;

    public Collection $batches;

    public Collection $orderItems;

    public Collection $inventories;

    public Location $location;

    public ?Member $member = null;

    public int $companyId;

    public OrderDiscountService $orderDiscountService;

    public OrderTaxService $orderTaxService;

    public StoreManager $storeManager;

    public function setDetails(
        StoreManager $storeManager,
        OrderData $orderData,
        Collection $products,
        Collection $batches,
        Collection $orderItems,
        Location $location,
        int $companyId,
    ): void {
        $this->orderData = $orderData;
        $this->products = $products;
        $this->batches = $batches;
        $this->orderItems = $orderItems;
        $this->location = $location;
        $this->companyId = $companyId;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $this->storeManager = $storeManagerQueries->loadEmployeeAndStores($storeManager);

        $this->orderDiscountService = resolve(OrderDiscountService::class);
        $this->orderDiscountService->setDetails($this);

        $this->orderTaxService = resolve(OrderTaxService::class);
        $this->orderTaxService->setDetails($this);

        $companyQueries = resolve(CompanyQueries::class);
        $this->company = $companyQueries->getConfigurationColumnsById($this->companyId);

        $productIds = $this->orderItems->pluck('id')->unique()->filter()->toArray();

        $inventoryQueries = resolve(InventoryQueries::class);
        $this->inventories = $inventoryQueries->getInventoriesByProductIds($this->location->getKey(), $productIds);

        $this->member = $this->getMember();
    }

    public function getMember(): ?Member
    {
        $memberId = $this->getExistingMemberId();

        if ($memberId) {
            $memberQueries = resolve(MemberQueries::class);
            $member = $memberQueries->memberExistsById($this->companyId, $memberId);

            if (! $member instanceof Member) {
                return null;
            }

            return $member;
        }

        return null;
    }

    public function getExistingMemberId(): ?int
    {
        if ($this->isMemberAttached()) {
            return $this->orderData->member_id;
        }

        return null;
    }

    public function checkRequestDetails(): void
    {
        $this->checkBillReferenceNumberDetails();

        if (! $this->hasOrderItems()) {
            return;
        }

        $this->checkMemberExists();

        $this->checkRecordsExists();

        $subtotal = 0;

        $cartSubtotalAfterDiscount = $this->getCartSubtotalAfterDiscount();
        $totalTaxAmount = $this->getTotalTaxAmount();

        $itemTotals = [];
        foreach ($this->orderItems as $orderItem) {
            $product = $this->products->firstWhere('id', $orderItem['id']);

            $this->checkProductPriceWithType($product, $orderItem);

            $this->checkOrderItem($product, $orderItem);

            $itemSubtotal = $this->getItemSubtotal($orderItem);
            $itemDiscounts = $this->orderDiscountService->getItemDiscountAmountFor($orderItem);
            $itemSubtotal -= $itemDiscounts['total_discount'];

            $cartSubtotal = $this->getCartSubtotal();
            $cartSubtotal -= $this->orderDiscountService->getTotalItemDiscountAmount();

            $cartDiscountAmountSplitByQuantity = $this->orderDiscountService->getItemCartDiscountAmount(
                $cartSubtotal,
                $itemSubtotal
            );

            $itemAmountAfterCartDiscountAmount = $itemSubtotal - $cartDiscountAmountSplitByQuantity;

            $itemTax = $this->orderTaxService->getItemTaxAmountFor(
                $itemAmountAfterCartDiscountAmount,
                $totalTaxAmount,
                $cartSubtotalAfterDiscount
            );

            $itemTotals[$orderItem['id']] = $itemAmountAfterCartDiscountAmount + $itemTax;

            $this->checkItemTotalPricePaid($product, $orderItem, $itemAmountAfterCartDiscountAmount + $itemTax);

            $subtotal += $itemSubtotal;
        }

        $cartDiscount = $this->orderDiscountService->getCartDiscountAmountFor($subtotal);

        $subtotal -= $cartDiscount['total_discount'];

        if ($this->hasPriceOverrideForCart()) {
            $subtotalBeforePriceOverrideDiscount = $subtotal + $cartDiscount['price_override_discount'];
            $this->orderDiscountService->checkPriceOverrideForCartDetails($subtotalBeforePriceOverrideDiscount);
        }

        $calculatedTotalTaxAmount = $this->orderTaxService->getTotalTaxAmountFor($subtotal);
        $receivedTotalTaxAmount = (float) $this->orderData->total_tax_amount;
        $this->orderTaxService->checkTaxDetails($calculatedTotalTaxAmount);

        $subtotal += null === $this->orderData->total_tax_amount
            ? $calculatedTotalTaxAmount
            : $receivedTotalTaxAmount;

        $subtotal += $this->getOrderRoundOffAmount($subtotal);

        $this->checkLayawayAuthorizer();

        $this->checkLayawayAmounts($subtotal);

        $this->checkCreditAuthorizer();

        $this->checkCreditAmounts($subtotal);

        $this->checkPaymentDetails($subtotal);
    }

    public function getPaymentAmount(): float
    {
        $payments = collect($this->orderData->payments);

        return $payments->sum('amount');
    }

    public function checkProductPriceWithType(Product $product, array $orderItem): void
    {
        if ($product->type_id === ProductTypes::REGULAR_PRODUCT->value && ! $this->isPriceAttached($orderItem)) {
            abort(412, 'Price is not provided for the product with the name ' . $product->name);
        }

        if ($product->type_id === ProductTypes::REGULAR_PRODUCT->value || $product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        if ($this->isOpenPriceAttached($orderItem)) {
            return;
        }

        abort(412, 'Open Price is not provided for the product with the name ' . $product->name);
    }

    public function isPriceAttached(array $orderItem): bool
    {
        return array_key_exists('price', $orderItem) && $orderItem['price'];
    }

    public function isOpenPriceAttached(array $orderItem): bool
    {
        return array_key_exists('open_price', $orderItem) && $orderItem['open_price'];
    }

    public function checkMemberExists(): void
    {
        if (! $this->isMemberAttached()) {
            return;
        }

        if (! $this->member instanceof Member) {
            abort(412, 'The selected member id is invalid.');
        }

        if ($this->member->status !== Status::ACTIVE->value) {
            abort(412, $this->orderData->member_id . ' specified member is deleted and currently in-Active. ');
        }
    }

    public function hasOrderItems(): bool
    {
        return $this->orderItems->isNotEmpty();
    }

    public function isMemberAttached(): bool
    {
        return $this->orderData->member_id && null !== $this->orderData->member_id;
    }

    public function isRoundOffValueProvided(): bool
    {
        return null !== $this->orderData->order_round_off_amount;
    }

    public function hasStoreManagerPriceOverride(array $orderItem): bool
    {
        return array_key_exists(
            'price_override_amount',
            $orderItem
        ) && 0.00 !== (float) $orderItem['price_override_amount'];
    }

    public function hasPriceOverride(array $orderItem): bool
    {
        return $this->hasStoreManagerPriceOverride($orderItem);
    }

    public function hasStoreManagerPriceOverrideForCart(): bool
    {
        return null !== $this->orderData->cart_price_override_amount;
    }

    public function hasPriceOverrideForCart(): bool
    {
        if (CommonFunctions::compareFloatNumbers($this->orderData->cart_price_override_amount ?? 0.00, 0.00)) {
            return false;
        }

        return $this->hasStoreManagerPriceOverrideForCart();
    }

    public function hasComplimentaryItem(array $orderItem): bool
    {
        return array_key_exists(
            'complimentary_item_reason_id',
            $orderItem
        ) && $orderItem['complimentary_item_reason_id'];
    }

    public function checkRecordsExists(): void
    {
        $this->checkProducts();

        $this->checkPaymentTypes();

        $this->checkPromoters();

        $this->checkLayawayDetails();

        $this->checkCreditDetails();
    }

    public function checkLayawayDetails(): void
    {
        if (! $this->orderData->is_layaway) {
            return;
        }

        if ($this->isMemberAttached()) {
            return;
        }

        abort(412, 'Please provide member or employee when a layaway order.');
    }

    public function checkCreditDetails(): void
    {
        if (! $this->orderData->is_credit) {
            return;
        }

        if ($this->isMemberAttached()) {
            return;
        }

        abort(412, 'Please provide member or employee when a credit sale.');
    }

    public function checkProducts(): void
    {
        if ($this->products->where('status', false)->isNotEmpty()) {
            abort(412, 'Some of the products are archived.');
        }

        if ($this->products->count() !== $this->orderItems->pluck('id')->unique()->count()) {
            abort(412, 'Some of the products are not in our records.');
        }
    }

    public function checkPaymentTypes(): void
    {
        if (null === $this->orderData->payments) {
            return;
        }

        $paymentIds = collect($this->orderData->payments)->pluck('type_id')->unique()->filter()->toArray();

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypes = $paymentTypeQueries->getByIds($paymentIds, $this->companyId);

        if ($paymentTypes->where('status', false)->isNotEmpty()) {
            abort(412, 'Some of the payment types are inactive.');
        }

        if (
            (! $this->orderData->member_id && ! $this->orderData->member_details)
            && $paymentTypes->where('is_member_required', true)->isNotEmpty()
        ) {
            abort(412, 'Member is required for one of the selected payment types.');
        }

        if ($paymentTypes->count() !== count($paymentIds)) {
            abort(412, 'Some of the payment types are not available in our records.');
        }
    }

    public function checkPromoters(): void
    {
        $promoterQueries = resolve(PromoterQueries::class);

        $promoterIds = $this->orderItems->pluck('promoter_ids')->collapse()->unique()->filter()->toArray();

        $allPromotersExist = $promoterQueries->doAllPromotersExist($promoterIds, $this->companyId);

        if (! $allPromotersExist) {
            abort(412, 'Some of the promoters are not available in our records.');
        }
    }

    public function checkOrderItem(Product $product, array $orderItem): void
    {
        $this->checkPromotersDetails($product, $orderItem);
        $this->checkAllowDecimalQty($product, $orderItem);
        $this->checkNegativeInventory($product, $orderItem);
        $this->checkBatchNumber($product, $orderItem);
        $this->orderDiscountService->checkItemWisePromotionDetails($product, $orderItem);
    }

    public function isBoxProductWithBoxProductIdAttached(Product $product, array $orderItem): bool
    {
        return $product->type_id === ProductTypes::REGULAR_PRODUCT->value
            && (array_key_exists('box_product_id', $orderItem) || array_key_exists('product_bundle_id', $orderItem))
            && ((isset($orderItem['box_product_id']) > 0) || (isset($orderItem['product_bundle_id']) > 0));
    }

    public function hasBatchNumber(array $orderItem): bool
    {
        return array_key_exists('batch_number', $orderItem) && $orderItem['batch_number'];
    }

    public function hasBatchExpiryDate(array $orderItem): bool
    {
        return array_key_exists('batch_expiry_date', $orderItem) && $orderItem['batch_expiry_date'];
    }

    public function checkBatchNumber(Product $product, array $orderItem): void
    {
        if ($product->has_batch) {
            if (! $this->hasBatchDetails($orderItem)) {
                abort(412, 'Batch Number is required for the product with name ' . $product->name . '.');
            }

            foreach ($orderItem['batch_details'] as $batchDetail) {
                if (! $this->hasBatchNumber($batchDetail)) {
                    abort(412, 'Batch Number is required for the product with name ' . $product->name . '.');
                }

                $batch = $this->batches->where('product_id', $orderItem['id'])
                    ->firstWhere('number', $batchDetail['batch_number']);

                if ($batch) {
                    return;
                }

                if (! $this->hasBatchExpiryDate($batchDetail)) {
                    abort(412, 'Batch Expiry Date is required for the product with name ' . $product->name . '.');
                }

                $batchQueries = resolve(BatchQueries::class);
                $batch = $batchQueries->addNew(
                    $this->companyId,
                    $product->id,
                    $batchDetail['batch_number'],
                    $batchDetail['batch_expiry_date'],
                );

                $this->batches->push($batch);
            }

            return;
        }
    }

    public function checkNegativeInventory(Product $product, array $orderItem): void
    {
        if ($this->company->allow_negative_inventory) {
            return;
        }

        $inventory = $this->inventories->firstWhere('product_id', $orderItem['id']);
        $quantity = $this->orderItems->where('id', $orderItem['id'])->sum('quantity');

        if ($inventory && (float) $inventory->stock >= (float) $quantity) {
            return;
        }

        abort(
            412,
            'Specified product (Named: ' . $product->name . ') does not have sufficient quantity available at the moment.'
        );
    }

    public function checkPromotersDetails(Product $product, array $orderItem): void
    {
        $companyMinimumPromoterPerItem = $this->company->min_promoters_per_item;

        if (0 === $companyMinimumPromoterPerItem) {
            return;
        }

        if (! $this->hasPromotesAttached($orderItem)) {
            abort(412, 'Specified product (Named: ' . $product->name . ') does not have any promoter(s) attached.');
        }

        $promoterIdsCount = is_countable($orderItem['promoter_ids']) ? count($orderItem['promoter_ids']) : 0;
        if ($promoterIdsCount < $companyMinimumPromoterPerItem) {
            abort(
                412,
                'Specified product (Named: ' . $product->name . ') requires a minimum of ' . $companyMinimumPromoterPerItem . ' promoter(s) but only ' . $promoterIdsCount . ' promoter(s) are attached.'
            );
        }
    }

    public function checkBillReferenceNumberDetails(): void
    {
        if (! $this->company->is_bill_reference_number_mandatory) {
            return;
        }

        if (null !== $this->orderData->bill_reference_number) {
            return;
        }

        abort(412, 'Bill reference number is required while new Order.');
    }

    public function checkAllowDecimalQty(Product $product, array $orderItem): void
    {
        if (! $product->unitOfMeasure) {
            return;
        }

        $unitOfMeasure = $product->unitOfMeasure;
        if ($unitOfMeasure->allow_decimal_qty) {
            return;
        }

        if (! Str::contains((string) $orderItem['quantity'], '.')) {
            return;
        }

        abort(412, 'Not allow decimal quantity for the product with the name ' . $product->name);
    }

    public function getItemSubtotal(array $item): float
    {
        if ($this->isPriceAttached($item)) {
            return CommonFunctions::numberFormat($item['price'] * $item['quantity']);
        }

        if ($this->isOpenPriceAttached($item)) {
            return CommonFunctions::numberFormat($item['open_price'] * $item['quantity']);
        }

        return 0.0;
    }

    public function getCartSubtotal(): float
    {
        $cartSubtotal = $this->orderItems->sum(fn ($orderItem): float => $this->getItemSubtotal($orderItem));

        return CommonFunctions::numberFormat($cartSubtotal);
    }

    public function getOrderRoundOffAmount(float $subtotal): float
    {
        if ($this->orderItems->isEmpty()) {
            return 0.00;
        }

        if (! $this->isRoundOffValueProvided()) {
            return 0.00;
        }

        $totalPricePaid = $this->orderItems->sum('total_price_paid');

        $orderRoundOff = RoundOffConfiguration::roundOffCalculationFor(
            CommonFunctions::numberFormatString($totalPricePaid)
        );

        if ($this->orderData->order_round_off_amount !== $orderRoundOff) {
            abort(
                412,
                'Round off value of ' . $this->orderData->order_round_off_amount . ' does not match with the expected value of ' . CommonFunctions::numberFormat(
                    $orderRoundOff
                )
            );
        }

        return $orderRoundOff;
    }

    public function checkPaymentDetails(float $subtotal): void
    {
        if ($this->orderData->is_layaway) {
            return;
        }

        if ($this->orderData->is_credit) {
            return;
        }

        $subtotal = CommonFunctions::numberFormat($subtotal);

        if ($subtotal <= 0) {
            return;
        }

        $payments = collect($this->orderData->payments);

        if ($subtotal && $payments->isEmpty()) {
            abort(412, 'Payment is required. Because of subtotal is ' . $subtotal);
        }

        $paymentsAmount = CommonFunctions::numberFormat($payments->sum('amount'));

        if ($subtotal > $paymentsAmount) {
            abort(
                412,
                'Specified payment amount of ' . $paymentsAmount . ' is short of the expected payment amount of ' . $subtotal
            );
        }

        if ($subtotal < $paymentsAmount) {
            abort(
                412,
                'Specified payment amount of ' . $paymentsAmount . ' is more than the expected payment amount of ' . $subtotal
            );
        }
    }

    public function checkItemTotalPricePaid(Product $product, array $orderItem, float $totalPricePaid): void
    {
        if (! array_key_exists('total_price_paid', $orderItem)) {
            abort(412, 'Item total price paid is not specified for ' . $product->name . ' product.');
        }

        $specifiedTotalPricePaid = (float) $orderItem['total_price_paid'];
        if (CommonFunctions::compareFloatNumbers($totalPricePaid, $specifiedTotalPricePaid)) {
            return;
        }

        abort(
            412,
            'Specified total price paid amount of ' . $specifiedTotalPricePaid . ' for ' . $product->name . ' product does not match with calculated amount of ' . $totalPricePaid
        );
    }

    public function getCartSubtotalAfterDiscount(): float
    {
        $cartSubtotal = $this->getCartSubtotal();
        $cartSubtotal -= $this->orderDiscountService->getTotalItemDiscountAmount();

        $cartDiscount = $this->orderDiscountService->getCartDiscountAmountFor($cartSubtotal);

        return $cartSubtotal - $cartDiscount['total_discount'];
    }

    public function getTotalTaxAmount(): float
    {
        $cartSubtotalAfterDiscount = $this->getCartSubtotalAfterDiscount();
        $totalTax = (float) $this->orderData->total_tax_amount;

        if (null === $this->orderData->total_tax_amount) {
            return $this->orderTaxService->getTotalTaxAmountFor($cartSubtotalAfterDiscount);
        }

        return $totalTax;
    }

    public function getCartSubtotalByDiscountApplicableType(float $cartSubtotal): float
    {
        if ($this->company->discount_applicable_type === DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value) {
            return $cartSubtotal;
        }

        return $this->getCartSubtotal();
    }

    public function getCurrentLocation(int $locationId, int $companyId): Location
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getById(
            $locationId,
            $companyId,
            (int) LocationTypes::STORE->value,
            [
                'id',
                'name',
                'code',
                'sales_tax_percentage',
                'sales_return_days_limit',
                'credit_note_expiration_days',
                'loyalty_point_expiration_days',
            ]
        );
    }

    public function hasBatchDetails(array $orderItem): bool
    {
        return array_key_exists('batch_details', $orderItem) && $orderItem['batch_details'];
    }

    private function hasPromotesAttached(array $orderItem): bool
    {
        return array_key_exists('promoter_ids', $orderItem) && $orderItem['promoter_ids'];
    }

    public function checkLayawayAuthorizer(): void
    {
    }

    public function checkLayawayAmounts(float $subtotal): void
    {
        if (! $this->orderData->is_layaway) {
            return;
        }

        if (null === $this->orderData->layaway_pending_amount) {
            abort(412, 'Layaway pending amount is not specified.');
        }

        $specifiedLayawayPendingAmount = $this->orderData->layaway_pending_amount;
        $calculatedLayawayPendingAmount = $this->getLayawayPendingAmount($subtotal);

        if (! CommonFunctions::compareFloatNumbers($specifiedLayawayPendingAmount, $calculatedLayawayPendingAmount)) {
            $message = 'Specified layaway pending amount does not match with calculated layaway pending amount.\nExpected: ' . $calculatedLayawayPendingAmount . '\\n' .
                'Specified: ' . $specifiedLayawayPendingAmount;

            abort(412, $message);
        }
    }

    public function getLayawayPendingAmount(float $subtotal): float
    {
        $payments = collect($this->orderData->payments);

        return $subtotal - $payments->sum('amount');
    }

    public function isLayawaySale(): ?bool
    {
        return $this->orderData->is_layaway;
    }

    public function checkCreditAuthorizer(): void
    {
        if (! $this->orderData->is_credit) {
            return;
        }

        /** @var Employee $storeManagerEmployee */
        $storeManagerEmployee = $this->storeManager->employee;
        if (! $storeManagerEmployee->getStatus()) {
            abort(
                412,
                'Specified Store Manager : ' . $storeManagerEmployee->getFullName() . ' account is inactive. Please contact admin.'
            );
        }
    }

    public function checkCreditAmounts(float $subtotal): void
    {
        if (! $this->orderData->is_credit) {
            return;
        }

        if (null === $this->orderData->credit_pending_amount) {
            abort(412, 'Credit pending amount is not specified.');
        }

        $specifiedCreditPendingAmount = $this->orderData->credit_pending_amount;
        $calculatedCreditPendingAmount = $this->getCreditPendingAmount($subtotal);

        if (! CommonFunctions::compareFloatNumbers($specifiedCreditPendingAmount, $calculatedCreditPendingAmount)) {
            $message = 'Specified credit pending amount does not match with calculated credit pending amount.\nExpected: ' . $calculatedCreditPendingAmount . '\\n' .
                'Specified: ' . $specifiedCreditPendingAmount;

            abort(412, $message);
        }
    }

    public function getCreditPendingAmount(float $subtotal): float
    {
        $payments = collect($this->orderData->payments);

        return $subtotal - $payments->sum('amount');
    }

    public function isCreditOrder(): ?bool
    {
        return $this->orderData->is_credit;
    }

    public function isBoxProductAttached(array $orderCartItem): ?bool
    {
        return (array_key_exists('box_product_id', $orderCartItem) || array_key_exists(
            'product_bundle_id',
            $orderCartItem
        )) && (isset($orderCartItem['box_product_id']) > 0 || isset($orderCartItem['product_bundle_id']) > 0);
    }
}
