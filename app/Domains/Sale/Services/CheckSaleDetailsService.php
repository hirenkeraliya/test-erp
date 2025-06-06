<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\BookingPaymentUseTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\Voucher\Services\GenerateVoucherService;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CheckSaleDetailsService
{
    public Company $company;

    /**
     * @var Collection<mixed, mixed>|mixed
     */
    public $bookingPayments;

    /**
     * @var Collection<mixed, mixed>|mixed
     */
    public $creditNotes;

    /**
     * @var Collection<mixed, mixed>|mixed
     */
    public $giftCards;

    public Collection $saleMismatches;

    public SaleData $saleData;

    public Collection $products;

    public Collection $cartItems;

    public Collection $batches;

    public Collection $derivatives;

    public Collection $inventories;

    public Location $location;

    public ?Member $member = null;

    public ?Employee $employee = null;

    public Cashier $cashier;

    public int $companyId;

    public int $appVersion;

    public SaleUserService $saleUserService;

    public SaleDiscountService $saleDiscountService;

    public SaleTaxService $saleTaxService;

    public SaleReturnService $saleReturnService;

    public GenerateVoucherService $generateVoucherService;

    public GenerateLoyaltyPointsService $generateLoyaltyPointsService;

    public SaleCashbackService $saleCashbackService;

    public function setDetails(
        SaleData $saleData,
        Collection $products,
        Collection $cartItems,
        Collection $batches,
        Location $location,
        Cashier $cashier,
        int $companyId,
        int $appVersion = 0,
    ): void {
        $this->saleData = $saleData;
        $this->products = $products;
        $this->cartItems = $cartItems;
        $this->batches = $batches;
        $this->location = $location;
        $this->cashier = $cashier;
        $this->companyId = $companyId;
        $this->appVersion = $appVersion;
        $this->saleMismatches = collect([]);

        $this->saleUserService = resolve(SaleUserService::class);
        $this->saleUserService->setDetails($this, $this->cashier);

        $this->saleDiscountService = resolve(SaleDiscountService::class);
        $this->saleDiscountService->setDetails($this);

        if ($this->hasCashback()) {
            $this->saleCashbackService = resolve(SaleCashbackService::class);
            $this->saleCashbackService->setDetails($this);
        }

        $this->derivatives = collect([]);
        $derivativeIds = $this->cartItems->pluck('derivative_id')->unique()->filter();
        if (0 !== $derivativeIds->count()) {
            $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
            $this->derivatives = $unitOfMeasureDerivativeQueries->getByIds($derivativeIds->toArray());
        }

        $this->bookingPayments = collect([]);
        $bookingPaymentIds = collect($this->saleData->payments)->pluck('booking_payment_id')->unique()->filter();
        if (0 !== $bookingPaymentIds->count()) {
            $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
            $this->bookingPayments = $bookingPaymentQueries->getByIds(
                $bookingPaymentIds->toArray(),
                $this->location->id
            );
        }

        $this->creditNotes = collect([]);
        $creditNoteIds = collect($this->saleData->payments)->pluck('credit_note_id')->unique()->filter();
        if (0 !== $creditNoteIds->count()) {
            $creditNoteQueries = resolve(CreditNoteQueries::class);
            $this->creditNotes = $creditNoteQueries->getByIds($creditNoteIds->toArray(), $this->location->id);
        }

        $this->giftCards = collect([]);
        $giftCardIds = collect($this->saleData->payments)->pluck('gift_card_id')->unique()->filter();
        if (0 !== $giftCardIds->count()) {
            $giftCardQueries = resolve(GiftCardQueries::class);
            $this->giftCards = $giftCardQueries->getByIds($giftCardIds->toArray(), $companyId);
        }

        $this->saleTaxService = resolve(SaleTaxService::class);
        $this->saleTaxService->setDetails($this);

        $this->saleReturnService = resolve(SaleReturnService::class);
        $this->saleReturnService->setDetails($this);

        $this->generateVoucherService = resolve(GenerateVoucherService::class);
        $this->generateVoucherService->setDetails($this);

        $this->generateLoyaltyPointsService = resolve(GenerateLoyaltyPointsService::class);
        $this->generateLoyaltyPointsService->setDetails($saleData->loyalty_points, $this->companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $this->company = $companyQueries->getConfigurationColumnsById($this->companyId);

        $productIds = $this->cartItems->pluck('id')->unique()->filter()->toArray();
        $inventoryQueries = resolve(InventoryQueries::class);
        $this->inventories = $inventoryQueries->getInventoriesByProductIds($this->location->getKey(), $productIds);

        $this->member = $this->getMember();
        $this->employee = $this->getEmployee();
    }

    public function getMember(): ?Member
    {
        if ($this->saleData->employee_id) {
            return $this->getEmployeeMember($this->saleData->employee_id);
        }

        $memberId = $this->saleUserService->getMemberId();
        if (! $memberId) {
            return null;
        }

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->memberExistsById($this->companyId, $memberId);
    }

    public function getEmployeeMember(int $employeeId): ?Member
    {
        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getByEmployeeIdWithEmployee($this->companyId, $employeeId);
    }

    public function getEmployee(): ?Employee
    {
        if (! $this->member instanceof Member) {
            return null;
        }

        return $this->member->employee;
    }

    public function getCurrentStore(Cashier $cashier): Location
    {
        $locationQueries = resolve(LocationQueries::class);

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        return $locationQueries->getLocationByCountersCounterUpdateId($cashier->counter_update_id);
    }

    public function getCurrentLocation(Cashier $cashier): Location
    {
        $locationQueries = resolve(LocationQueries::class);

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        return $locationQueries->getLocationByCountersCounterUpdateId($cashier->counter_update_id);
    }

    public function checkRequestDetails(bool $companyAllowExchangeToDifferentStore): void
    {
        $this->checkOfflineSaleId();

        $this->checkBillReferenceNumberDetails();

        if ($this->saleReturnService->hasReturnItems()) {
            $this->saleReturnService->checkReturnItems($this->location->id, $companyAllowExchangeToDifferentStore);
        }

        if (! $this->hasCartItems()) {
            return;
        }

        if ($this->isMemberAttached() && $this->hasMemberDetails()) {
            abort(412, 'Please provide either member id or member details, not both.');
        }

        $this->checkMemberExists();

        $this->checkEmployeeExists();

        $this->checkEmployeePurchaseLimit();

        $this->checkRecordsExists();

        $subtotal = 0;
        $cartSubtotalAfterDiscount = $this->getCartSubtotalAfterDiscount();
        $totalTaxAmount = $this->getTotalTaxAmount();

        $itemTotals = [];
        foreach ($this->cartItems as $cartItem) {
            $product = $this->products->firstWhere('id', $cartItem['id']);

            $this->checkProductPriceWithType($product, $cartItem);

            $this->checkCartItem($product, $cartItem);

            $itemSubtotal = $this->getItemSubtotal($cartItem);
            $itemDiscounts = $this->saleDiscountService->getItemDiscountAmountFor($cartItem);
            $itemSubtotal -= $itemDiscounts['total_discount'];

            $cartSubtotal = $this->getCartSubtotal();
            $cartSubtotal -= $this->saleDiscountService->getTotalItemDiscountAmount();

            $cartDiscountAmountSplitByQuantity = $this->saleDiscountService->getItemCartDiscountAmount(
                $cartSubtotal,
                $itemSubtotal,
                $cartItem
            );

            $itemAmountAfterCartDiscountAmount = $itemSubtotal - $cartDiscountAmountSplitByQuantity;

            $itemTax = $this->saleTaxService->getItemTaxAmountFor(
                $itemAmountAfterCartDiscountAmount,
                $totalTaxAmount,
                $cartSubtotalAfterDiscount
            );

            $itemTotals[$cartItem['id']] = $itemAmountAfterCartDiscountAmount + $itemTax;

            $this->checkItemTotalPricePaid($product, $cartItem, $itemAmountAfterCartDiscountAmount + $itemTax);

            $subtotal += $itemSubtotal;
        }

        $this->saleDiscountService->checkCartWidePromotionDetails($subtotal);

        $cartDiscount = $this->saleDiscountService->getCartDiscountAmountFor($subtotal);

        $subtotal -= $cartDiscount['total_discount'];

        if ($this->hasVoucher()) {
            $subtotalBeforeVoucherDiscount = $subtotal + $cartDiscount['total_discount'] - $cartDiscount['cart_wide_discount'];
            $this->saleDiscountService->checkVoucherDetails($subtotalBeforeVoucherDiscount);
        }

        if ($this->hasLoyaltyPointsForCart()) {
            $this->checkLoyaltyPointsCartDiscount();
        }

        if ($this->hasPriceOverrideForCart()) {
            $subtotalBeforePriceOverrideDiscount = $subtotal + $cartDiscount['total_discount'] - $cartDiscount['cart_wide_discount'] - $cartDiscount['voucher_discount'] - $cartDiscount['cart_wide_loyalty_point_discount'];
            $this->saleDiscountService->checkPriceOverrideForCartDetails($subtotalBeforePriceOverrideDiscount);
        }

        $calculatedTotalTaxAmount = $this->saleTaxService->getTotalTaxAmountFor($subtotal);
        $receivedTotalTaxAmount = (float) $this->saleData->total_tax_amount;
        $this->saleTaxService->checkTaxDetails($calculatedTotalTaxAmount);

        $subtotal += null === $this->saleData->total_tax_amount
            ? $calculatedTotalTaxAmount
            : $receivedTotalTaxAmount;

        $subtotal += $this->getSaleRoundOffAmount($subtotal);

        if ($this->saleReturnService->hasReturnItems()) {
            $returnSubtotal = $this->saleReturnService->getReturnItemsSubtotal();
            $subtotal -= ($returnSubtotal + (float) $this->saleData->sale_return_round_off_amount);
        }

        $this->generateVoucherService->checkVouchers($subtotal);

        if ($this->hasCashback()) {
            $this->saleCashbackService->checkForApplicability($subtotal);
        }

        if ($this->hasGenerateLoyaltyPoints()) {
            $loyaltyPointsMismatches = $this->generateLoyaltyPointsService->checkLoyaltyPoints(
                $itemTotals,
                $this,
                $subtotal,
                $this->getPaymentAmount(),
                $this->member?->id,
                $this->saleData->happened_at
            );

            $this->saleMismatches = $this->saleMismatches->merge($loyaltyPointsMismatches);
        }

        $this->checkLayawayAuthorizer();

        $this->checkLayawayAmounts($subtotal);

        $this->checkCreditAuthorizer();

        $this->checkCreditAmounts($subtotal);

        $this->checkPaymentDetails($subtotal);
    }

    public function checkLayawayAuthorizer(): void
    {
        if (! $this->saleData->is_layaway) {
            return;
        }

        /** @var CompanySetting $companySetting */
        $companySetting = $this->company->companySetting;

        $checkCompanySettingService = resolve(CheckCompanySettingService::class);
        $checkCompanySettingService->setDetails($companySetting);
        $checkCompanySettingService->checkLayawaySaleSettings($this->saleData, $this->saleMismatches);

        if (! ($this->saleData->layaway_store_manager_id && $this->saleData->layaway_store_manager_passcode)) {
            $saleMismatchMessage = 'Store Manager id & passcode is required to authorized layaway sale';
            abort(412, $saleMismatchMessage);
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getByIdWithEmployee(
            $this->saleData->layaway_store_manager_id,
            $this->companyId
        );

        if (! $storeManager) {
            $saleMismatchMessage = 'Specified Store Manager does not correspond with our records.';
            abort(412, $saleMismatchMessage);
        }

        $this->checkLayawayStoreManagerAuthorizationCode();

        /** @var Employee $employee */
        $employee = $storeManager->employee;
        if (! $employee->getStatus()) {
            $saleMismatchMessage = 'Specified Store Manager : ' . $employee->getFullName() . ' account is inactive. Please contact admin.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if (! $storeManager->passcode) {
            return;
        }

        if ($storeManager->passcode === $this->saleData->layaway_store_manager_passcode) {
            return;
        }

        $saleMismatchMessage = 'The Store Manager provided passcode for authorization does not correspond with our records.';
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkLayawayStoreManagerAuthorizationCode(): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $this->saleMismatches,
            (int) $this->saleData->layaway_store_manager_id,
            $this->saleData->layaway_store_manager_authorization_code,
            $this->saleData->happened_at
        );
    }

    public function checkCreditAuthorizer(): void
    {
        if (! $this->saleData->is_credit_sale) {
            return;
        }

        /** @var CompanySetting $companySetting */
        $companySetting = $this->company->companySetting;

        $checkCompanySettingService = resolve(CheckCompanySettingService::class);
        $checkCompanySettingService->setDetails($companySetting);
        $checkCompanySettingService->checkCreditSaleSettings($this->saleData, $this->saleMismatches);

        if (! ($this->saleData->credit_store_manager_id && $this->saleData->credit_store_manager_passcode)) {
            $saleMismatchMessage = 'Store Manager id & passcode is required to authorized credit sale';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getByIdWithEmployee(
            $this->saleData->credit_store_manager_id,
            $this->companyId
        );

        if (! $storeManager) {
            $saleMismatchMessage = 'Specified Store Manager does not correspond with our records.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $this->checkCreditStoreManagerAuthorizationCode();

        /** @var Employee $employee */
        $employee = $storeManager->employee;
        if (! $employee->getStatus()) {
            $saleMismatchMessage = 'Specified Store Manager : ' . $employee->getFullName() . ' account is inactive. Please contact admin.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if (! $storeManager->passcode) {
            return;
        }

        if ($storeManager->passcode === $this->saleData->credit_store_manager_passcode) {
            return;
        }

        $saleMismatchMessage = 'The Store Manager provided passcode for authorization does not correspond with our records.';
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkCreditStoreManagerAuthorizationCode(): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $this->saleMismatches,
            (int) $this->saleData->credit_store_manager_id,
            $this->saleData->credit_store_manager_authorization_code,
            $this->saleData->happened_at
        );
    }

    public function checkCreditAmounts(float $subtotal): void
    {
        if (! $this->saleData->is_credit_sale) {
            return;
        }

        if (! $this->company->allow_credit_sale) {
            $saleMismatchMessage = 'Please note that credit sales are not permitted with our company.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if (null === $this->saleData->credit_pending_amount) {
            $saleMismatchMessage = 'Credit pending amount is not specified.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $specifiedCreditPendingAmount = $this->saleData->credit_pending_amount;
        $calculatedCreditPendingAmount = $this->getCreditPendingAmount($subtotal);

        if (! CommonFunctions::compareFloatNumbers($specifiedCreditPendingAmount, $calculatedCreditPendingAmount)) {
            $saleMismatchMessage = 'Specified credit pending amount does not match with calculated credit pending amount.\nExpected: ' . $calculatedCreditPendingAmount . '\\n' .
                'Specified: ' . $specifiedCreditPendingAmount;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function getCreditPendingAmount(float $subtotal): float
    {
        $payments = collect($this->saleData->payments);

        return $subtotal - $payments->sum('amount');
    }

    public function getPaymentAmount(): float
    {
        $payments = collect($this->saleData->payments);

        return $payments->sum('amount');
    }

    public function hasGenerateLoyaltyPoints(): bool
    {
        return collect($this->saleData->loyalty_points)->isNotEmpty();
    }

    public function checkBoxProductPrice(Product $product, array $cartItem): void
    {
        if ($product->type_id !== ProductTypes::REGULAR_PRODUCT->value) {
            return;
        }

        if (! $this->isBoxProductAttached($cartItem)) {
            return;
        }

        if (
            $this->hasProductLoyaltyPoints($cartItem)
            && ! $this->isPriceAttached($cartItem)
        ) {
            $cartItem['price'] = 0;
        }

        $this->checkBoxProductBoxRetailPrice($product, $cartItem);
    }

    public function checkBoxProductBoxRetailPrice(Product $product, array $cartItem): void
    {
        if (! $this->isBoxProductAttached($cartItem)) {
            return;
        }

        $boxProductId = $cartItem['box_product_id'] ?? $cartItem['product_bundle_id'];

        $productBox = $product->boxes->firstWhere('id', $boxProductId);
        if (! $productBox) {
            abort(412, 'Product Box not in our record');
        }

        // TODO: Temporary due to pos is not able create sale
        if (0.00 === $productBox->retail_price) {
            return;
        }

        if (! $productBox->retail_price) {
            $saleMismatchMessage = 'Product box price is not available for the product with the name ' . $product->name;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if (
            CommonFunctions::compareFloatNumbers((float) $productBox->retail_price, (float) $cartItem['price'])
        ) {
            return;
        }

        if ($this->isExchange($cartItem)) {
            return;
        }

        $saleMismatchMessage = 'Provided price not match with product bundle price with the name ' . $product->name;
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkProductPriceWithType(Product $product, array $cartItem): void
    {
        // When Product Loyalty Points discount apply in frontend then remove it
        if ($this->hasProductLoyaltyPoints($cartItem)) {
            return;
        }

        // TODO: Temporary add isPriceAttachedWithZero due to pos is not able create sale
        if (
            (
                $product->type_id === ProductTypes::REGULAR_PRODUCT->value
                || $product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value
                || $product->type_id === ProductTypes::SERIAL_PRODUCT->value
            )
            && ! $this->isPriceAttachedWithZero($cartItem)
        ) {
            abort(412, 'Price is not provided for the product with the name ' . $product->name);
        }

        if (
            $product->type_id === ProductTypes::REGULAR_PRODUCT->value
            || $product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value
            || $product->type_id === ProductTypes::SERIAL_PRODUCT->value
        ) {
            return;
        }

        if ($this->isOpenPriceAttached($cartItem)) {
            return;
        }

        // TODO: Temporary add mismatch due to pos is not able to sync sale
        $saleMismatchMessage = 'Open Price is not provided for the product with the name ' . $product->name;
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkProductSoldAsSingleItem(Product $product): void
    {
        if (! $product->is_sold_as_single_item) {
            $saleMismatchMessage = 'Do not allow master product for sale with ' . $product->name;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function isPriceAttached(array $cartItem): bool
    {
        return array_key_exists('price', $cartItem) && $cartItem['price'];
    }

    public function isPriceAttachedWithZero(array $cartItem): bool
    {
        return array_key_exists('price', $cartItem) && $cartItem['price'] >= 0;
    }

    public function isBoxProductAttached(array $cartItem): bool
    {
        return (array_key_exists('box_product_id', $cartItem) || array_key_exists('product_bundle_id', $cartItem))
                && ((isset($cartItem['box_product_id']) > 0) || (isset($cartItem['product_bundle_id']) > 0));
    }

    public function isBoxProductWithBoxProductIdAttached(Product $product, array $cartItem): bool
    {
        return $product->type_id === ProductTypes::REGULAR_PRODUCT->value
                && (array_key_exists('box_product_id', $cartItem) || array_key_exists('product_bundle_id', $cartItem))
                && ((isset($cartItem['box_product_id']) > 0) || (isset($cartItem['product_bundle_id']) > 0));
    }

    public function isOpenPriceAttached(array $cartItem): bool
    {
        return array_key_exists('open_price', $cartItem) && $cartItem['open_price'];
    }

    public function checkOfflineSaleId(): void
    {
        if ($this->saleReturnService->hasReturnItems()) {
            $this->checkOfflineSaleIdSaleReturn();
        }

        if (! $this->hasCartItems()) {
            return;
        }

        $this->checkOfflineSaleIdSale();
    }

    public function checkSerialNumber(Product $product, array $cartItem): void
    {
        if ($product->type_id !== ProductTypes::SERIAL_PRODUCT->value) {
            return;
        }

        if (! $this->hasSerialNumberDetails($cartItem)) {
            abort(412, 'Serial Number is required for the product with name ' . $product->name . '.');
        }

        foreach ($cartItem['serial_number_details'] as $serialNumberDetail) {
            if (
                $product->type_id === ProductTypes::SERIAL_PRODUCT->value && ! $this->hasSerialNumberAttached(
                    $serialNumberDetail
                )
            ) {
                abort(412, 'Serial Number is required for one of the selected product type.');
            }

            if (! $this->hasSerialNumberAttached($serialNumberDetail)) {
                continue;
            }

            $serialNumberQueries = resolve(SerialNumberQueries::class);
            $serialNumber =
            $serialNumberQueries->getByCompanyIdAndSerialNumber($this->companyId, $serialNumberDetail['serial_number']);

            if (! $serialNumber) {
                $saleMismatchMessage = $serialNumberDetail['serial_number'] . ' specified serial number is not match in our records.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

                continue;
            }

            if ($serialNumber->product_id !== $product->id) {
                abort(
                    412,
                    $serialNumberDetail['serial_number'] . ' specified serial number and specified product not match.'
                );
            }

            if ($serialNumber->status === SerialNumberStatus::ACTIVE->value) {
                continue;
            }

            $saleMismatchMessage = $serialNumberDetail['serial_number'] . ' specified serial number is already used.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        /** @var array $serialNumberDetails */
        $serialNumberDetails = $cartItem['serial_number_details'];
        if (
            (float) collect($serialNumberDetails)->count()
            !== (float) $cartItem['quantity']
        ) {
            $saleMismatchMessage = 'Sale total quantity mismatch for the product with name ' . $product->name . '.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkOfflineSaleIdSaleReturn(): void
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        if ($saleReturnQueries->doesOfflineSaleReturnIdExist($this->saleData->offline_sale_id, $this->companyId)) {
            abort(412, 'The offline sale id has already been taken.');
        }
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
            $saleMismatchMessage = $this->saleData->member_id . ' specified member is deleted and currently in-Active. ';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkEmployeeExists(): void
    {
        if (! $this->saleData->employee_id) {
            return;
        }

        if ($this->employee instanceof Employee) {
            return;
        }

        abort(412, 'The selected employee id is invalid.');
    }

    public function checkEmployeePurchaseLimit(): void
    {
        if (! $this->saleData->employee_id) {
            return;
        }

        if (! $this->employee instanceof Employee) {
            return;
        }

        if (! $this->employee->employeeGroup instanceof EmployeeGroup) {
            return;
        }

        if ($this->employee->employeeGroup->item_purchase_limit <= 0) {
            return;
        }

        /** @var Employee $employee */
        $employee = $this->employee;

        /** @var EmployeeGroup $employeeGroup */
        $employeeGroup = $employee->employeeGroup;
        $employeePurchaseLimit = $employeeGroup->item_purchase_limit;

        if ((int) $employeeGroup->purchase_limit_type_id === PurchaseLimitTypes::BY_ITEMS->value) {
            $currentCartItemsQuantities = $this->cartItems->sum('quantity');

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_WEEK->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByWeekLimit($employeeGroup->limit_reset);
                $totalBuyingQuantities = (int) $currentCartItemsQuantities + $this->getTotalQuantities(
                    $previousDate,
                    $currentDate,
                    $employee->id
                );

                if ($totalBuyingQuantities > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by items with by week is: ' . $employeePurchaseLimit . 'but, overall total buying quantities: ' . $totalBuyingQuantities;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_MONTH->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByMonthLimit($employeeGroup->limit_reset);

                $totalBuyingQuantities = (int) $currentCartItemsQuantities + $this->getTotalQuantities(
                    $previousDate,
                    $currentDate,
                    $employee->id
                );

                if ($totalBuyingQuantities > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by items with by month is: ' . $employeePurchaseLimit . 'but, overall total buying quantities: ' . $totalBuyingQuantities;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_DAYS->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByDaysLimit($employeeGroup->limit_reset);

                $totalBuyingQuantities = (int) $currentCartItemsQuantities + $this->getTotalQuantities(
                    $previousDate,
                    $currentDate,
                    $employee->id
                );

                if ($totalBuyingQuantities > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by items with by days is: ' . $employeePurchaseLimit . 'but, overall total buying quantities: ' . $totalBuyingQuantities;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }
        }

        if ((int) $employeeGroup->purchase_limit_type_id === PurchaseLimitTypes::BY_AMOUNT->value) {
            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_WEEK->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByWeekLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                $finalPurchaseAmounts = ($this->getPaymentAmount() + (float) $sales->sum(
                    'total_amount_paid'
                )) - (float) $saleReturns->sum('total_price_paid');

                if ($finalPurchaseAmounts > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by amount with by week is: ' . $employeePurchaseLimit . 'but, overall total buying amounts: ' . $finalPurchaseAmounts;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_MONTH->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByMonthLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                $finalPurchaseAmounts = ($this->getPaymentAmount() + (float) $sales->sum(
                    'total_amount_paid'
                )) - (float) $saleReturns->sum('total_price_paid');

                if ($finalPurchaseAmounts > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by amount with by month is: ' . $employeePurchaseLimit . 'but, overall total buying amounts: ' . $finalPurchaseAmounts;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_DAYS->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByDaysLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                $finalPurchaseAmounts = ($this->getPaymentAmount() + (float) $sales->sum(
                    'total_amount_paid'
                )) - (float) $saleReturns->sum('total_price_paid');

                if ($finalPurchaseAmounts > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by amounts with by days is: ' . $employeePurchaseLimit . 'but, overall total buying amounts: ' . $finalPurchaseAmounts;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }
        }

        if ((int) $employeeGroup->purchase_limit_type_id === PurchaseLimitTypes::BY_SALE->value) {
            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_WEEK->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByWeekLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                $finalSalesCount = (1 + $sales->count()) - $saleReturns->count();

                if ($finalSalesCount > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by sale with by week is: ' . $employeePurchaseLimit . 'but, overall total buying sale counts: ' . $finalSalesCount;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_MONTH->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByMonthLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                $finalSalesCount = (1 + $sales->count()) - $saleReturns->count();

                if ($finalSalesCount > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by sale with by month is: ' . $employeePurchaseLimit . 'but, overall total buying sale counts: ' . $finalSalesCount;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_DAYS->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByDaysLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                $finalSalesCount = (1 + $sales->count()) - $saleReturns->count();

                if ($finalSalesCount > $employeePurchaseLimit) {
                    $saleMismatchMessage = 'Employee: ' . $employee->getFullName() . ' have purchase limit by sale with by days is: ' . $employeePurchaseLimit . 'but, overall total buying sale counts: ' . $finalSalesCount;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }
            }
        }
    }

    public function checkOfflineSaleIdSale(): void
    {
        $saleQueries = resolve(SaleQueries::class);
        if ($saleQueries->doesOfflineSaleIdExist($this->saleData->offline_sale_id, $this->companyId)) {
            abort(412, 'The offline sale id has already been taken.');
        }
    }

    public function hasCartItems(): bool
    {
        return $this->cartItems->isNotEmpty();
    }

    public function hasDerivativeAttached(array $cartItem): bool
    {
        return array_key_exists('derivative_id', $cartItem) && $cartItem['derivative_id'];
    }

    public function hasSerialNumberAttached(array $serialNumberDetails): bool
    {
        return array_key_exists('serial_number', $serialNumberDetails) && $serialNumberDetails['serial_number'];
    }

    public function hasCartPromotion(): bool
    {
        return (bool) $this->saleData->cart_promotion_id;
    }

    public function hasVoucher(): bool
    {
        return (bool) $this->saleData->voucher_number;
    }

    public function isMemberAttached(): bool
    {
        return $this->saleData->member_id && null !== $this->saleData->member_id;
    }

    public function hasMemberDetails(): bool
    {
        return collect($this->saleData->member)->isNotEmpty();
    }

    public function isLayawaySale(): ?bool
    {
        return $this->saleData->is_layaway;
    }

    public function isCreditSale(): ?bool
    {
        return $this->saleData->is_credit_sale;
    }

    public function isRoundOffValueProvided(): bool
    {
        return null !== $this->saleData->sale_round_off_amount;
    }

    public function hasLoyaltyPoints(array $payment): bool
    {
        return array_key_exists('loyalty_points', $payment) && $payment['loyalty_points'];
    }

    public function hasItemPromotion(array $cartItem): bool
    {
        if (! array_key_exists('promotion_id', $cartItem)) {
            return false;
        }

        if (! array_key_exists('item_discount_amount', $cartItem)) {
            return false;
        }

        if (! $cartItem['promotion_id']) {
            return false;
        }

        return (bool) (float) $cartItem['item_discount_amount'];
    }

    public function hasDreamPrice(array $cartItem): bool
    {
        return (
            array_key_exists('dream_price_id', $cartItem) && null !== $cartItem['dream_price_id']
        ) && (
            array_key_exists('dream_price_amount', $cartItem) && null !== $cartItem['dream_price_amount']
        );
    }

    public function hasStoreManagerPriceOverride(array $cartItem): bool
    {
        return (array_key_exists('store_manager_id', $cartItem) && null !== $cartItem['store_manager_id']) &&
            (array_key_exists('store_manager_passcode', $cartItem) && null !== $cartItem['store_manager_passcode']) &&
            (array_key_exists('price_override_amount', $cartItem) && null !== $cartItem['price_override_amount']);
    }

    public function hasDirectorPriceOverride(array $cartItem): bool
    {
        return (array_key_exists('director_id', $cartItem) && null !== $cartItem['director_id']) &&
            (array_key_exists('director_passcode', $cartItem) && null !== $cartItem['director_passcode']) &&
            (array_key_exists('price_override_amount', $cartItem) && null !== $cartItem['price_override_amount']);
    }

    public function hasCashierPriceOverride(array $cartItem): bool
    {
        return (array_key_exists('cashier_id', $cartItem) && null !== $cartItem['cashier_id']) &&
            (array_key_exists('price_override_amount', $cartItem) && null !== $cartItem['price_override_amount']);
    }

    public function hasPriceOverride(array $cartItem): bool
    {
        if ($this->hasStoreManagerPriceOverride($cartItem)) {
            return true;
        }

        if ($this->hasDirectorPriceOverride($cartItem)) {
            return true;
        }

        return $this->hasCashierPriceOverride($cartItem);
    }

    public function hasStoreManagerPriceOverrideForCart(): bool
    {
        return null !== $this->saleData->store_manager_id && null !== $this->saleData->store_manager_passcode && null !== $this->saleData->cart_price_override_amount;
    }

    public function hasDirectorPriceOverrideForCart(): bool
    {
        return null !== $this->saleData->director_id && null !== $this->saleData->director_passcode && null !== $this->saleData->cart_price_override_amount;
    }

    public function hasCashierPriceOverrideForCart(): bool
    {
        return null !== $this->saleData->cashier_id && null !== $this->saleData->cart_price_override_amount;
    }

    public function hasPriceOverrideForCart(): bool
    {
        if (CommonFunctions::compareFloatNumbers($this->saleData->cart_price_override_amount ?? 0.00, 0.00)) {
            return false;
        }

        if ($this->hasStoreManagerPriceOverrideForCart()) {
            return true;
        }

        if ($this->hasDirectorPriceOverrideForCart()) {
            return true;
        }

        return $this->hasCashierPriceOverrideForCart();
    }

    public function hasLoyaltyPointsForCart(): bool
    {
        if (CommonFunctions::compareFloatNumbers($this->saleData->cart_loyalty_point_amount ?? 0.00, 0.00)) {
            return false;
        }

        return $this->saleData->cart_loyalty_points > 0;
    }

    public function hasComplimentaryItem(array $cartItem): bool
    {
        return array_key_exists('complimentary_item_reason_id', $cartItem) && $cartItem['complimentary_item_reason_id'];
    }

    public function hasHappyHourDiscount(array $cartItem): bool
    {
        return array_key_exists('happy_hours_offline_id', $cartItem)
            && $cartItem['happy_hours_offline_id']
            && array_key_exists('happy_hours_discount_amount', $cartItem)
            && $cartItem['happy_hours_discount_amount'];
    }

    public function hasComplimentaryAuthorizer(array $cartItem): bool
    {
        if ($this->hasDirector($cartItem)) {
            return true;
        }

        return $this->hasStoreManager($cartItem);
    }

    public function hasDirector(array $cartItem): bool
    {
        return (array_key_exists('director_passcode', $cartItem) && $cartItem['director_passcode']) &&
            (array_key_exists('director_id', $cartItem) && $cartItem['director_id']);
    }

    public function hasStoreManager(array $cartItem): bool
    {
        return (array_key_exists('store_manager_passcode', $cartItem) && $cartItem['store_manager_passcode']) &&
            (array_key_exists('store_manager_id', $cartItem) && $cartItem['store_manager_id']);
    }

    public function hasCashback(): bool
    {
        return (
            $this->saleData->cashback_id && null !== $this->saleData->cashback_id
        ) && (
            $this->saleData->cashback_amount && null !== $this->saleData->cashback_amount
        );
    }

    public function checkRecordsExists(): void
    {
        $this->checkProducts();

        $this->checkPaymentTypes();

        $this->checkPromoters();

        $this->checkLayawayDetails();

        $this->checkCreditDetails();
    }

    public function checkProducts(): void
    {
        if ($this->products->where('status', false)->isNotEmpty()) {
            $saleMismatchMessage = 'Some of the products are archived.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if ($this->products->count() !== $this->cartItems->pluck('id')->unique()->count()) {
            abort(412, 'Some of the products are not in our records.');
        }
    }

    public function checkPaymentTypes(): void
    {
        if (null === $this->saleData->payments) {
            return;
        }

        $paymentIds = collect($this->saleData->payments)->pluck('type_id')->unique()->filter()->toArray();

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypes = $paymentTypeQueries->getByIds($paymentIds, $this->companyId);

        if ($paymentTypes->where('status', false)->isNotEmpty()) {
            $saleMismatchMessage = 'Some of the payment types are inactive.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if (
            (! $this->saleData->member_id && ! $this->saleData->member) &&
            $paymentTypes->where('is_member_required', true)->isNotEmpty()
        ) {
            $saleMismatchMessage = 'Member is required for one of the selected payment types.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if ($paymentTypes->count() !== count($paymentIds)) {
            abort(412, 'Some of the payment types are not available in our records.');
        }

        $payments = $this->saleData->payments;
        $this->validateCreditNotes($payments);
        $this->validateGiftCards($payments);
        $this->validateBookingPayment($payments);
        $this->checkLoyaltyPoints();
    }

    public function validateCreditNotes(array $payments): void
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $memberId = $this->saleUserService->getExistingMemberId();

        foreach ($payments as $payment) {
            $payment = (array) $payment;

            if ((int) $payment['type_id'] === StaticPaymentTypes::CREDIT_NOTE->value &&
                ! array_key_exists('credit_note_id', $payment)
            ) {
                abort(412, 'Credit note id must be provided when payment type is credit note.');
            }

            if (! array_key_exists('credit_note_id', $payment)) {
                continue;
            }

            if (! $payment['credit_note_id']) {
                continue;
            }

            $creditNote = $this->creditNotes->firstWhere('id', $payment['credit_note_id']);

            if (! $creditNote) {
                abort(412, 'Some of the credit notes are not available in our records.');
            }

            if ($creditNote->expiry_date && $creditNote->expiry_date < now()->format('Y-m-d')) {
                $saleMismatchMessage = 'Credit note is expired. You are not able to use expired credit notes.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if ($creditNote->status !== CreditNoteStatuses::ACTIVE->value) {
                $saleMismatchMessage = 'Credit note is not active.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if ($memberId && $memberId !== $creditNote->member_id) {
                abort(412, 'Selected member is not same as the credit note member');
            }

            if ((int) $payment['type_id'] !== StaticPaymentTypes::CREDIT_NOTE->value) {
                abort(412, 'The Payment Type must be a credit note when you provide the credit note id.');
            }

            if ($creditNote->available_amount < $payment['amount']) {
                $saleMismatchMessage = 'Specified payment amount exceeds the credit note available amount ' . $creditNote->available_amount . ' Requested Payment Amount is ' . $payment['amount'];
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            $creditNoteCompanyId = $counterUpdateQueries->getCompanyIdByCounterUpdateId(
                $creditNote->counter_update_id
            );

            if ($this->companyId !== $creditNoteCompanyId) {
                abort(412, 'You cannot use different companies credit notes.');
            }
        }
    }

    public function validateBookingPayment(array $payments): void
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $memberId = $this->saleUserService->getExistingMemberId();

        foreach ($payments as $payment) {
            $payment = (array) $payment;

            if ((int) $payment['type_id'] === StaticPaymentTypes::BOOKING_PAYMENT->value &&
                ! array_key_exists('booking_payment_id', $payment)
            ) {
                abort(412, 'Booking Payment id must be provided when payment type is booking payment.');
            }

            if (array_key_exists('booking_payment_id', $payment) && $payment['booking_payment_id']) {
                $bookingPayment = $this->bookingPayments->firstWhere('id', $payment['booking_payment_id']);

                if (! $bookingPayment) {
                    abort(412, 'Some of the booking payments are not available in our records.');
                }

                if ($bookingPayment->status !== BookingPaymentStatuses::ACTIVE->value) {
                    $saleMismatchMessage = 'Booking Payment is not active.';
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }

                if ($bookingPayment->member_id && (int) $memberId !== $bookingPayment->member_id) {
                    abort(412, 'Selected member is not same as the booking payment member');
                }

                if ((int) $payment['type_id'] !== StaticPaymentTypes::BOOKING_PAYMENT->value) {
                    abort(412, 'The Payment Type must be a booking payment when you provide the booking payment id.');
                }

                $bookingPaymentCompanyId = $counterUpdateQueries->getCompanyIdByCounterUpdateId(
                    $bookingPayment->counter_update_id
                );

                if ($this->companyId !== $bookingPaymentCompanyId) {
                    abort(412, 'You cannot use different companies booking payments.');
                }

                if ($bookingPayment->available_amount < $payment['amount']) {
                    $saleMismatchMessage = 'Specified payment amount ' . $payment['amount'] . ' is more than available amount of the booking payment ' . $bookingPayment->available_amount;
                    CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
                }

                if ($this->company->booking_payment_use_type === BookingPaymentUseTypes::PARTIALLY->value) {
                    return;
                }

                if (
                    CommonFunctions::compareFloatNumbers(
                        (float) $bookingPayment->available_amount,
                        (float) $payment['amount']
                    )
                ) {
                    return;
                }

                $saleMismatchMessage = 'You cannot use booking payment partially. kindly use full booking payment. Specified payment amount is ' . $payment['amount'] . ' and available booking payment amount is ' . $bookingPayment->available_amount;
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }
        }
    }

    public function checkLayawayDetails(): void
    {
        if (! $this->saleData->is_layaway) {
            return;
        }

        if ($this->saleData->layaway_pending_amount <= 0) {
            abort(412, 'Layaway pending amount is not allow 0 or less than 0');
        }

        if ($this->isMemberAttached()) {
            return;
        }

        if ($this->hasMemberDetails()) {
            return;
        }

        if ($this->saleData->employee_id) {
            return;
        }

        abort(412, 'Please provide member or employee when a layaway sale.');
    }

    public function checkCreditDetails(): void
    {
        if (! $this->saleData->is_credit_sale) {
            return;
        }

        if ($this->saleData->credit_pending_amount <= 0) {
            abort(412, 'Credit pending amount is not allow 0 or less than 0.');
        }

        if ($this->isMemberAttached()) {
            return;
        }

        if ($this->hasMemberDetails()) {
            return;
        }

        if (
            $this->member instanceof Member
            && $this->member->employee_id
            && ! $this->company->allow_employee_credit_sale
        ) {
            $saleMismatchMessage = 'The employee is not authorized to make purchases through credit sale.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if ($this->saleData->employee_id) {
            return;
        }

        abort(412, 'Please provide member or employee when a credit sale.');
    }

    public function checkPromoters(): void
    {
        $promoterQueries = resolve(PromoterQueries::class);

        $promoterIds = $this->cartItems->pluck('promoter_ids')->collapse()->unique()->filter()->toArray();

        $allPromotersExist = $promoterQueries->doAllPromotersExist($promoterIds, $this->companyId);

        if (! $allPromotersExist) {
            abort(412, 'Some of the promoters are not available in our records.');
        }
    }

    public function checkCartItem(Product $product, array $cartItem): void
    {
        $this->checkPromotersDetails($product, $cartItem);
        $this->checkDerivativeDetails($product, $cartItem);
        $this->checkAllowDecimalQty($product, $cartItem);
        $this->checkPriceMismatch($product, $cartItem);
        $this->checkProductLoyaltyPoints($product, $cartItem);
        $this->checkBatchNumber($product, $cartItem);
        $this->checkNegativeInventory($product, $cartItem);
        $this->checkProductSoldAsSingleItem($product);
        $this->checkSerialNumber($product, $cartItem);
        $this->saleDiscountService->checkItemWisePromotionDetails($product, $cartItem);
    }

    public function checkNegativeInventory(Product $product, array $cartItem): void
    {
        if ($this->company->allow_negative_inventory) {
            return;
        }

        $inventory = $this->inventories->firstWhere('product_id', $cartItem['id']);
        $quantity = $this->cartItems->where('id', $cartItem['id'])->sum('quantity');

        if ($inventory && (float) $inventory->stock >= (float) $quantity) {
            return;
        }

        $saleMismatchMessage = 'Specified product (Named: ' . $product->name . ') does not have sufficient quantity available at the moment.';
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkPromotersDetails(Product $product, array $cartItem): void
    {
        $companyMinimumPromoterPerItem = $this->company->min_promoters_per_item;

        if (0 === $companyMinimumPromoterPerItem) {
            return;
        }

        if (! $this->hasPromotesAttached($cartItem)) {
            $saleMismatchMessage = 'Specified product (Named: ' . $product->name . ') does not have any promoter(s) attached.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $promoterIdsCount = is_countable($cartItem['promoter_ids']) ? count($cartItem['promoter_ids']) : 0;
        if ($promoterIdsCount < $companyMinimumPromoterPerItem) {
            $saleMismatchMessage = 'Specified product (Named: ' . $product->name . ') requires a minimum of ' . $companyMinimumPromoterPerItem . ' promoter(s) but only ' . $promoterIdsCount . ' promoter(s) are attached.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkBillReferenceNumberDetails(): void
    {
        if (! $this->company->is_bill_reference_number_mandatory) {
            return;
        }

        if (null !== $this->saleData->bill_reference_number) {
            return;
        }

        $saleMismatchMessage = 'Bill reference number is required while new sale.';
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkDerivativeDetails(Product $product, array $cartItem): void
    {
        if (! $this->hasDerivativeAttached($cartItem)) {
            return;
        }

        if (! $product->sell_item_via_derivative) {
            $saleMismatchMessage = 'Specified product (Named: ' . $product->name . ') cannot be sold via derivative.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if (null === $product->unit_of_measure_id) {
            $saleMismatchMessage = 'Specified product (Named: ' . $product->name . ') does not have unit of measure.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $derivative = $this->derivatives->firstWhere('id', $cartItem['derivative_id']);

        if (! $derivative) {
            abort(412, 'Specified derivative id is not available in our records.');
        }

        if ($derivative->unit_of_measure_id !== $product->unit_of_measure_id) {
            $saleMismatchMessage = 'Specified derivative ' . $derivative->name . ' does not match with products (Named: ' . $product->name . ') unit of measure.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkAllowDecimalQty(Product $product, array $cartItem): void
    {
        if (! $product->unitOfMeasure) {
            return;
        }

        $unitOfMeasure = $product->unitOfMeasure;
        if ($unitOfMeasure->allow_decimal_qty) {
            return;
        }

        if (! Str::contains((string) $cartItem['quantity'], '.')) {
            return;
        }

        $saleMismatchMessage = 'Not allow decimal quantity for the product with the name ' . $product->name;
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkPriceMismatch(Product $product, array $item): void
    {
        $productMinimumPrice = $product->minimum_price;
        $productName = $product->name;
        $item['open_price'] = array_key_exists('open_price', $item) ? $item['open_price'] : 0;

        if (
            $product->type_id !== ProductTypes::REGULAR_PRODUCT->value &&
            $product->type_id !== ProductTypes::ASSEMBLY_PRODUCT->value &&
            $product->type_id !== ProductTypes::SERIAL_PRODUCT->value &&
            (float) $item['open_price'] < (float) $productMinimumPrice &&
            ! $this->isExchange($item)
        ) {
            $saleMismatchMessage = 'The open price of ' . $item['open_price'] . ' for the product with the name ' . $productName . ' is less than the minimum price of ' . $productMinimumPrice;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $this->checkRegularProductPrice($product, $item);
        $this->checkBoxProductPrice($product, $item);
    }

    public function checkRegularProductPrice(Product $product, array $item): void
    {
        if (
            $product->type_id !== ProductTypes::REGULAR_PRODUCT->value
            && $product->type_id !== ProductTypes::ASSEMBLY_PRODUCT->value
            && $product->type_id !== ProductTypes::SERIAL_PRODUCT->value
        ) {
            return;
        }

        if (
            $product->type_id === ProductTypes::REGULAR_PRODUCT->value
            && $this->isBoxProductAttached($item)
        ) {
            return;
        }

        if (
            $this->hasProductLoyaltyPoints($item)
            && ! $this->isPriceAttached($item)
        ) {
            $item['price'] = 0;
        }

        // TODO: Temporary due to pos is not able create sale
        if (! $product->retail_price) {
            return;
        }

        // TODO: Temporary due to pos is not able create sale
        if (0.00 === $product->retail_price) {
            return;
        }

        if (! $product->retail_price) {
            $saleMismatchMessage = 'Price is not available for the product with the name ' . $product->name;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        $productActualPrice = CommonFunctions::numberFormat((float) $product->retail_price);
        $itemPrice = CommonFunctions::numberFormat((float) $item['price']);

        if ($productActualPrice === $itemPrice) {
            return;
        }

        if ($this->isExchange($item)) {
            return;
        }

        $saleMismatchMessage = 'Product retail price mismatched. Actual Product retail price is ' . $productActualPrice . ' And Given product retail price is ' . $itemPrice;
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkProductLoyaltyPoints(Product $product, array $item): void
    {
        if (! $this->hasProductLoyaltyPoints($item)) {
            return;
        }

        if ($this->isBoxProductWithBoxProductIdAttached($product, $item)) {
            $this->checkBoxProductLoyaltyPoints($product, $item);

            return;
        }

        $this->checkRegularProductLoyaltyPoints($product, $item);
    }

    public function checkRegularProductLoyaltyPoints(Product $product, array $item): void
    {
        $member = $this->checkUserLoyaltyPoints();

        if ($product->tiers->isEmpty()) {
            $saleMismatchMessage = 'The specified product cannot be purchased using loyalty points.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $productLoyaltyPoint = $product->tiers->firstWhere('membership_id', $member->membership_id);
        if (! $productLoyaltyPoint) {
            $saleMismatchMessage = 'The specified product cannot be purchased using loyalty points.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        /** @var ProductLoyaltyPoint $productLoyaltyPoint */
        $productLoyaltyPoints = (int) ($productLoyaltyPoint->points * $item['quantity']);

        if ((int) $item['loyalty_points'] === $productLoyaltyPoints) {
            return;
        }

        $saleMismatchMessage = 'Product loyalty points mismatched. Actual Product loyalty points is ' . $productLoyaltyPoints . ' And Given product loyalty points is ' . $item['loyalty_points'];

        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function checkBoxProductLoyaltyPoints(Product $product, array $item): void
    {
        $member = $this->checkUserLoyaltyPoints();

        if ($product->boxes->isEmpty()) {
            $saleMismatchMessage = 'The specified product cannot be purchased using loyalty points.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $boxProductId = $item['box_product_id'] ?? $item['product_bundle_id'];

        $productBox = $product->boxes->firstWhere('id', $boxProductId);

        if (! $productBox) {
            $saleMismatchMessage = 'The specified product cannot be purchased using loyalty points.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        if ($productBox->boxProductLoyaltyPoints->isEmpty()) {
            $saleMismatchMessage = 'The specified product cannot be purchased using loyalty points.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $productLoyaltyPoint = $productBox->boxProductLoyaltyPoints->firstWhere(
            'membership_id',
            $member->membership_id
        );

        if (! $productLoyaltyPoint) {
            $saleMismatchMessage = 'The specified product cannot be purchased using loyalty points.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        /** @var ProductLoyaltyPoint $productLoyaltyPoint */
        $productLoyaltyPoints = (int) ($productLoyaltyPoint->points * $item['quantity']);

        if ((int) $item['loyalty_points'] === $productLoyaltyPoints) {
            return;
        }

        $saleMismatchMessage = 'Product loyalty points mismatched. Actual Product loyalty points is ' . $productLoyaltyPoints . ' And Given product loyalty points is ' . $item['loyalty_points'];

        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function getBoxProductUnits(array $cartItem): float
    {
        $product = $this->products->firstWhere('id', $cartItem['id']);

        if ($this->isBoxProductWithBoxProductIdAttached($product, $cartItem)) {
            $boxProductId = $cartItem['box_product_id'] ?? $cartItem['product_bundle_id'];
            $productBox = $product->boxes->firstWhere('id', $boxProductId);

            return (float) $productBox->units;
        }

        return 1.00;
    }

    public function checkBatchNumber(Product $product, array $cartItem): void
    {
        if ($product->has_batch) {
            if (! $this->hasBatchDetails($cartItem)) {
                abort(412, 'Batch Number is required for the product with name ' . $product->name . '.');
            }

            $productBoxUnits = $this->getBoxProductUnits($cartItem);

            foreach ($cartItem['batch_details'] as $batchDetail) {
                if (! $this->hasBatchNumber($batchDetail)) {
                    abort(412, 'Batch Number is required for the product with name ' . $product->name . '.');
                }

                $batch = $this->batches->where('product_id', $cartItem['id'])
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

            /** @var array $batchDetails */
            $batchDetails = $cartItem['batch_details'];
            if (
                (float) collect($batchDetails)->pluck('quantity')->sum()
                !== $cartItem['quantity'] * $productBoxUnits
            ) {
                $saleMismatchMessage = 'Sale total quantity mismatch for the product with name ' . $product->name . '.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            return;
        }

        if (array_key_exists('batch_details', $cartItem)) {
            $saleMismatchMessage = 'Batch number is not required for the product with name ' . $product->name . '.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
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
        $cartSubtotal = $this->cartItems->sum(fn ($cartItem): float => $this->getItemSubtotal($cartItem));

        return CommonFunctions::numberFormat($cartSubtotal);
    }

    public function getSaleRoundOffAmount(float $subtotal): float
    {
        if ($this->cartItems->isEmpty()) {
            return 0.00;
        }

        if (! $this->isRoundOffValueProvided()) {
            return 0.00;
        }

        $totalPricePaid = $this->cartItems->sum('total_price_paid');

        $saleRoundOff = RoundOffConfiguration::roundOffCalculationFor(
            CommonFunctions::numberFormatString($totalPricePaid)
        );

        if ($this->saleData->sale_round_off_amount !== $saleRoundOff) {
            $saleMismatchMessage = 'Round off value of ' . $this->saleData->sale_round_off_amount . ' does not match with the expected value of ' . CommonFunctions::numberFormat(
                $saleRoundOff
            );
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        return $saleRoundOff;
    }

    public function getLayawayPendingAmount(float $subtotal): float
    {
        $payments = collect($this->saleData->payments);

        return $subtotal - $payments->sum('amount');
    }

    public function checkLayawayAmounts(float $subtotal): void
    {
        if (! $this->saleData->is_layaway) {
            return;
        }

        if (null === $this->saleData->layaway_pending_amount) {
            $saleMismatchMessage = 'Layaway pending amount is not specified.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $specifiedLayawayPendingAmount = $this->saleData->layaway_pending_amount;
        $calculatedLayawayPendingAmount = $this->getLayawayPendingAmount($subtotal);

        if (! CommonFunctions::compareFloatNumbers($specifiedLayawayPendingAmount, $calculatedLayawayPendingAmount)) {
            $saleMismatchMessage = 'Specified layaway pending amount does not match with calculated layaway pending amount.\nExpected: ' . $calculatedLayawayPendingAmount . '\\n' .
                'Specified: ' . $specifiedLayawayPendingAmount;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkPaymentDetails(float $subtotal): void
    {
        $payments = collect($this->saleData->payments);
        $items = collect($this->saleData->items);

        $this->checkPaymentCurrency($payments);

        if ($items->whereNotNull('is_exchange')->count() > 0) {
            return;
        }

        $paymentsAmount = CommonFunctions::numberFormat($payments->sum('amount'));

        $subtotal = CommonFunctions::numberFormat($subtotal);

        if ($subtotal > 0 && $subtotal < $paymentsAmount) {
            $saleMismatchMessage = 'Specified payment amount of ' . $paymentsAmount . ' is more than the expected payment amount of ' . $subtotal;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if ($this->saleData->is_layaway) {
            $this->checkCreditAndLayawaySaleMismatch(
                $subtotal,
                $this->saleData->layaway_pending_amount,
                $paymentsAmount
            );

            return;
        }

        if ($this->saleData->is_credit_sale) {
            $this->checkCreditAndLayawaySaleMismatch(
                $subtotal,
                $this->saleData->credit_pending_amount,
                $paymentsAmount
            );

            return;
        }

        if ($subtotal > 0 && $subtotal && $payments->isEmpty()) {
            $saleMismatchMessage = 'Payment is required. Because of subtotal is ' . $subtotal;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        if ($subtotal > $paymentsAmount) {
            $saleMismatchMessage = 'Specified payment amount of ' . $paymentsAmount . ' is short of the expected payment amount of ' . $subtotal;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkPaymentCurrency(Collection $payments): void
    {
        $currencyIds = [];
        $currencyRates = [];

        foreach ($this->company->countries as $country) {
            $currencyIds[] = $country->currency?->id;
            $currencyRates[] = CommonFunctions::numberFormat((float) $country->currency?->currencyRate?->rate);
        }

        foreach ($payments as $payment) {
            if (! array_key_exists('currency_id', $payment)) {
                continue;
            }

            if (! in_array($payment['currency_id'], $currencyIds)) {
                $saleMismatchMessage = 'Payment currency id ' . $payment['currency_id'] . ' is not available in this company.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if (! array_key_exists('current_currency_rate', $payment)) {
                continue;
            }

            if (! in_array($payment['current_currency_rate'], $currencyRates)) {
                $saleMismatchMessage = 'Payment currency rate ' . $payment['current_currency_rate'] . ' does not match with the actual currency rate of ' . implode(
                    ', ',
                    $currencyRates
                ) . ' for the currency id ' . $payment['currency_id'];
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if (! array_key_exists('currency_amount', $payment)) {
                continue;
            }

            $currencyAmount = CommonFunctions::numberFormat(
                CommonFunctions::numberFormat((float) $payment['currency_amount']) / CommonFunctions::numberFormat(
                    (float) $payment['current_currency_rate']
                )
            );

            if (! CommonFunctions::compareFloatNumbers($currencyAmount, (float) $payment['amount'])) {
                $saleMismatchMessage = 'Payment amount ' . $payment['amount'] . ' does not match with the actual currency amount of ' . $currencyAmount . '.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }
        }
    }

    public function checkCreditAndLayawaySaleMismatch(
        float $subTotal,
        ?float $pendingAmount,
        float $paymentAmount
    ): void {
        $totalAmount = $subTotal - $pendingAmount;
        if (! CommonFunctions::compareFloatNumbers($totalAmount, $paymentAmount)) {
            $saleMismatchMessage = 'Specified payment amount of ' . $paymentAmount . ' is more than the expected payment amount of ' . $totalAmount;
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkLoyaltyPoints(): void
    {
        $payment = [];
        $payments = collect($this->saleData->payments)->where('type_id', StaticPaymentTypes::LOYALTY_POINT->value);

        if ($payments->isEmpty()) {
            return;
        }

        $member = $this->checkUserLoyaltyPoints();

        foreach ($payments as $payment) {
            if (! $this->hasLoyaltyPoints($payment)) {
                abort(412, 'Loyalty Points must be provided when payment type is loyalty point');
            }
        }

        $paymentLoyaltyPoints = $payments->sum('loyalty_points');

        $amountFromLoyaltyPoints = 0;
        if ($member->membership && $member->membership->loyalty_points_per_currency_unit > 0) {
            $amountFromLoyaltyPoints = CommonFunctions::numberFormat(
                $paymentLoyaltyPoints / $member->membership->loyalty_points_per_currency_unit
            );
        }

        $this->checkLoyaltyPointsIsValidOrNot($member, $paymentLoyaltyPoints);
        if (! CommonFunctions::compareFloatNumbers($amountFromLoyaltyPoints, (float) $payment['amount'])) {
            $saleMismatchMessage = 'The specified amount (' . $payment['amount'] . ') is more than the calculated amount from the loyalty points as per the membership of the user (' . $amountFromLoyaltyPoints . ').';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkLoyaltyPointsCartDiscount(): void
    {
        if (! $this->hasLoyaltyPointsForCart()) {
            return;
        }

        $member = $this->checkUserLoyaltyPoints();

        $paymentLoyaltyPoints = $this->saleData->cart_loyalty_points;
        $amountFromLoyaltyPoints = 0;
        if ($member->membership && $member->membership->loyalty_points_per_currency_unit > 0) {
            $amountFromLoyaltyPoints = CommonFunctions::numberFormat(
                $paymentLoyaltyPoints / $member->membership->loyalty_points_per_currency_unit
            );
        }

        $this->checkLoyaltyPointsIsValidOrNot($member, $paymentLoyaltyPoints);

        if (! CommonFunctions::compareFloatNumbers(
            $amountFromLoyaltyPoints,
            (float) $this->saleData->cart_loyalty_point_amount
        )) {
            $saleMismatchMessage = 'The specified amount (' . $this->saleData->cart_loyalty_point_amount . ') is more than the calculated amount from the loyalty points as per the membership of the user (' . $amountFromLoyaltyPoints . ').';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkLoyaltyPointsIsValidOrNot(Member $member, ?int $paymentLoyaltyPoints): void
    {
        if ($member->membership) {
            $minPoints = $member->membership->min_loyalty_points_for_redemption;
            $maxPoints = $member->membership->max_loyalty_points_for_redemption;

            if (! ($paymentLoyaltyPoints >= $minPoints && $paymentLoyaltyPoints <= $maxPoints)) {
                $saleMismatchMessage = 'The specified loyalty points (' . $paymentLoyaltyPoints . ') are not valid. Loyalty points must be between ' . $minPoints . ' and ' . $maxPoints . '.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }
        }
    }

    public function checkUserLoyaltyPoints(): Member
    {
        $saleUserService = resolve(SaleUserService::class);
        $saleUserService->setDetails($this, $this->cashier);

        $member = $saleUserService->getMember();

        if (null === $member) {
            abort(412, 'User is compulsory when payment type is loyalty point');
        }

        if (! $member->membership_id) {
            abort(412, 'Loyalty points can only be used when membership is assigned to the user.');
        }

        $payments = collect($this->saleData->payments)->where('type_id', StaticPaymentTypes::LOYALTY_POINT->value);
        $paymentLoyaltyPoints = (int) ($this->saleData->cart_loyalty_points + $payments->sum(
            'loyalty_points'
        ) + $this->cartItems->sum('loyalty_points'));

        if ($member->loyalty_points < $paymentLoyaltyPoints) {
            $saleMismatchMessage = 'Specified loyalty points are more than the current loyalty points balance of the user.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
        }

        return $member;
    }

    public function hasBatchNumber(array $cartItem): bool
    {
        return array_key_exists('batch_number', $cartItem) && $cartItem['batch_number'];
    }

    public function hasBatchDetails(array $cartItem): bool
    {
        return array_key_exists('batch_details', $cartItem) && $cartItem['batch_details'];
    }

    public function hasSerialNumberDetails(array $cartItem): bool
    {
        return array_key_exists('serial_number_details', $cartItem) && $cartItem['serial_number_details'];
    }

    public function hasBatchExpiryDate(array $cartItem): bool
    {
        return array_key_exists('batch_expiry_date', $cartItem) && $cartItem['batch_expiry_date'];
    }

    public function hasItemPromoCode(array $cartItem): bool
    {
        return array_key_exists('promo_code', $cartItem) && null !== $cartItem['promo_code'];
    }

    public function isExchange(array $cartItem): bool
    {
        return array_key_exists('is_exchange', $cartItem) && $cartItem['is_exchange'];
    }

    public function checkItemTotalPricePaid(Product $product, array $cartItem, float $totalPricePaid): void
    {
        if (! array_key_exists('total_price_paid', $cartItem)) {
            $saleMismatchMessage = 'Item total price paid is not specified for ' . $product->name . ' product.';
            CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);

            return;
        }

        $specifiedTotalPricePaid = (float) $cartItem['total_price_paid'];
        if (CommonFunctions::compareFloatNumbers($totalPricePaid, $specifiedTotalPricePaid)) {
            return;
        }

        if ($this->isExchange($cartItem)) {
            return;
        }

        $saleMismatchMessage = 'Specified total price paid amount of ' . $specifiedTotalPricePaid . ' for ' . $product->name . ' product does not match with calculated amount of ' . $totalPricePaid;
        CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
    }

    public function getCartSubtotalAfterDiscount(): float
    {
        $cartSubtotal = $this->getCartSubtotal();
        $cartSubtotal -= $this->saleDiscountService->getTotalItemDiscountAmount();

        $cartDiscount = $this->saleDiscountService->getCartDiscountAmountFor($cartSubtotal);

        return $cartSubtotal - $cartDiscount['total_discount'];
    }

    public function getTotalTaxAmount(): float
    {
        $cartSubtotalAfterDiscount = $this->getCartSubtotalAfterDiscount();
        $totalTax = (float) $this->saleData->total_tax_amount;

        if (null === $this->saleData->total_tax_amount) {
            return $this->saleTaxService->getTotalTaxAmountFor($cartSubtotalAfterDiscount);
        }

        return $totalTax;
    }

    public function validateGiftCards(array $payments): void
    {
        foreach ($payments as $payment) {
            $payment = (array) $payment;

            if ((int) $payment['type_id'] === StaticPaymentTypes::GIFT_CARD->value &&
                ! array_key_exists('gift_card_id', $payment)
            ) {
                abort(412, 'Gift Card id must be provided when payment type is gift card.');
            }

            if (! array_key_exists('gift_card_id', $payment)) {
                continue;
            }

            if (! $payment['gift_card_id']) {
                continue;
            }

            $giftCard = $this->giftCards->firstWhere('id', $payment['gift_card_id']);

            if (! $giftCard) {
                abort(412, 'Some of the gift cards are not available in our records.');
            }

            /** @var Carbon $saleHappenedAt */
            $saleHappenedAt = Carbon::createFromFormat('Y-m-d H:i:s', $this->saleData->happened_at);

            if ($giftCard->expiry_date && $giftCard->expiry_date < $saleHappenedAt->format('Y-m-d')) {
                $saleMismatchMessage = 'Expired gift card (number - [' . $giftCard->number . ']) was used for making a payment.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if ($giftCard->type_id === GiftCardTypes::SINGLE_USE_ONLY->value && $giftCard->status === GiftCardStatuses::USED->value) {
                $saleMismatchMessage = 'Specified Gift card (number - [' . $giftCard->number . ']) is single use only.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if ($giftCard->status !== GiftCardStatuses::ACTIVE->value) {
                $saleMismatchMessage = 'Gift card (number - [' . $giftCard->number . ']) is not active.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if ($giftCard->available_amount < $payment['amount']) {
                $saleMismatchMessage = 'The requested payment amount of ' . $payment['amount'] . 'exceeds the available amount of the gift card (number - [' . $giftCard->number . ']) , which is ' . $giftCard->available_amount . '.';
                CommonFunctions::addMismatchOrAbort($this->saleMismatches, $saleMismatchMessage);
            }

            if ($this->companyId !== $giftCard->company_id) {
                abort(412, 'You cannot use different companies gift card.');
            }
        }
    }

    public function getCartSubtotalByDiscountApplicableType(float $cartSubtotal): float
    {
        if ($this->company->discount_applicable_type === DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value) {
            return $cartSubtotal;
        }

        return $this->getCartSubtotal();
    }

    public function getItemSubtotalByDiscountApplicableType(float $itemTotal, array $cartItem): float
    {
        if ($this->company->discount_applicable_type === DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value) {
            return $itemTotal;
        }

        return $this->getItemSubtotal($cartItem);
    }

    public function hasProductLoyaltyPoints(array $cartItem): bool
    {
        if (! array_key_exists('loyalty_points', $cartItem)) {
            return false;
        }

        if (! $cartItem['loyalty_points']) {
            return false;
        }

        return (bool) (int) $cartItem['loyalty_points'];
    }

    private function preparedDateRangeByWeekLimit(int $limitReset): array
    {
        $weekDayName = LimitResetDays::getFormattedCaseName($limitReset);
        /** @var Carbon $previousDate */
        $previousDate = Carbon::parse($weekDayName)->previous();
        $previousDateFormat = $previousDate->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');

        return [$previousDateFormat, $currentDate];
    }

    private function preparedDateRangeByMonthLimit(int $limitReset): array
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $dateOfMonthDay = Carbon::now()->day($limitReset);
        $previousDate = $dateOfMonthDay->format('Y-m-d');
        if ($dateOfMonthDay->format('Y-m-d') > $currentDate) {
            $previousDate = $dateOfMonthDay->subMonth()->format('Y-m-d');
        }

        return [$previousDate, $currentDate];
    }

    private function preparedDateRangeByDaysLimit(int $limitReset): array
    {
        $previousDate = now()->subDays($limitReset)->format('Y-m-d');
        $currentDate = now()->format('Y-m-d');

        return [$previousDate, $currentDate];
    }

    private function getTotalQuantities(string $previousDate, string $currentDate, int $employeeId): int
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        $saleTotalQuantitiesPurchased = $saleItemQueries->getTotalQuantitiesBy(
            $previousDate,
            $currentDate,
            $employeeId
        );

        $saleReturnTotalQuantities = $saleReturnItemQueries->getTotalQuantitiesBy(
            $previousDate,
            $currentDate,
            $employeeId
        );

        return $saleTotalQuantitiesPurchased - $saleReturnTotalQuantities;
    }

    private function fetchSalesAndSaleReturns(string $previousDate, string $currentDate, int $employeeId): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        $sales = $saleQueries->getSalesByEmployeeWithDateRange($previousDate, $currentDate, $employeeId);

        $saleReturns = $saleReturnQueries->getSaleReturnsByEmployeeWithDateRange(
            $previousDate,
            $currentDate,
            $employeeId
        );

        return [$sales, $saleReturns];
    }

    private function hasPromotesAttached(array $cartItem): bool
    {
        return array_key_exists('promoter_ids', $cartItem) && $cartItem['promoter_ids'];
    }
}
