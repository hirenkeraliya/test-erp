<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\Voucher\Services\GenerateVoucherECommerceService;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CheckOrderEcommerceDetailsService
{
    public Company $company;

    public OrderECommerceData $orderECommerceData;

    public Collection $products;

    public Collection $orderItems;

    public Collection $inventories;

    public Location $location;

    public ?Member $member = null;

    public int $companyId;

    public OrderTaxService $orderTaxService;

    public SaleChannel $saleChannel;

    public GenerateVoucherECommerceService $generateVoucherECommerceService;

    public GenerateEcommerceLoyaltyPointsService $generateEcommerceLoyaltyPointsService;

    public OrderEcommerceDiscountService $orderEcommerceDiscountService;

    public Collection $orderMismatches;

    public function setDetails(
        OrderECommerceData $orderECommerceData,
        Collection $products,
        Collection $orderItems,
        SaleChannel $saleChannel,
    ): void {
        $this->orderECommerceData = $orderECommerceData;
        $this->products = $products;
        $this->orderItems = $orderItems;
        $this->location = $this->getCurrentLocation($saleChannel->getDefaultLocationId(), $saleChannel->getCompanyId());
        $this->companyId = $saleChannel->getCompanyId();
        $this->saleChannel = $saleChannel;
        $this->orderMismatches = collect([]);

        $companyQueries = resolve(CompanyQueries::class);
        $this->company = $companyQueries->getConfigurationColumnsById($this->companyId);

        $this->member = $this->getMember();

        $this->generateVoucherECommerceService = resolve(GenerateVoucherECommerceService::class);
        $this->generateVoucherECommerceService->setDetails($this);

        $this->orderEcommerceDiscountService = resolve(OrderEcommerceDiscountService::class);
        $this->orderEcommerceDiscountService->setDetails($this);

        $this->generateEcommerceLoyaltyPointsService = resolve(GenerateEcommerceLoyaltyPointsService::class);
        $this->generateEcommerceLoyaltyPointsService->setDetails($orderECommerceData->loyalty_points, $this->companyId);
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
            return $this->orderECommerceData->member_id;
        }

        if (
            null !== $this->orderECommerceData->member_details
            && [] !== $this->orderECommerceData->member_details
            && [[]] !== $this->orderECommerceData->member_details
        ) {
            $memberDetails = $this->orderECommerceData->member_details;

            $memberServices = resolve(MemberService::class);
            $member = $memberServices->checkRequiredMemberColumnsForEcommerce($memberDetails, $this->companyId);

            if ($member instanceof Member) {
                return $member->getKey();
            }

            return $memberServices->addNewMemberFromEcommerceOrder(
                $memberDetails,
                $this->companyId,
                $this->location->id
            );
        }

        return null;
    }

    public function checkRequestDetails(): void
    {
        if (! $this->hasOrderItems()) {
            return;
        }

        $this->checkMemberExists();

        $this->checkRecordsExists();

        $subtotal = 0;
        $itemTotals = [];
        foreach ($this->orderItems as $orderItem) {
            $product = $this->products->firstWhere('id', $orderItem['id']);

            $this->checkProductPriceWithType($product, $orderItem);

            $this->checkCartItem($orderItem);

            $itemSubtotal = $this->getItemSubtotal($orderItem);

            $itemDiscounts = $this->orderEcommerceDiscountService->getItemDiscountAmountFor($orderItem);
            $itemSubtotal -= $itemDiscounts['total_discount'];

            $subtotal += $itemSubtotal;

            $itemTotals[$orderItem['id']] = $itemSubtotal;
        }

        $orderDiscount = $this->orderEcommerceDiscountService->getOrderDiscountAmountFor($subtotal);

        $this->orderEcommerceDiscountService->checkCartWidePromotionDetails($subtotal);
        $beforeCartDiscountSubtotal = $subtotal - $orderDiscount['cart_wide_discount'];
        $this->orderEcommerceDiscountService->checkVoucherDetails($beforeCartDiscountSubtotal);

        $subtotalAfterVoucherDiscount = $subtotal - (int) $orderDiscount['total_discount'];
        $this->generateVoucherECommerceService->checkVouchers($subtotalAfterVoucherDiscount);

        if ($this->hasGenerateLoyaltyPoints()) {
            $happenedAtFormat = $this->getHappenedAtFormat();
            $loyaltyPointsMismatches = $this->generateEcommerceLoyaltyPointsService->checkLoyaltyPoints(
                $itemTotals,
                $this,
                $subtotalAfterVoucherDiscount,
                $this->getPaymentAmount(),
                $this->member?->id,
                $happenedAtFormat->format('Y-m-d H:i:s')
            );

            $this->orderMismatches = $this->orderMismatches->merge($loyaltyPointsMismatches);
        }

        $useEcommerceLoyaltyPointsService = resolve(UseEcommerceLoyaltyPointsService::class);
        $useEcommerceLoyaltyPointsService->checkLoyaltyPointsCartDiscount($this);
    }

    public function checkCartItem(array $cartItem): void
    {
        $this->orderEcommerceDiscountService->checkItemWisePromotionDetails($cartItem);
    }

    public function hasGenerateLoyaltyPoints(): bool
    {
        return collect($this->orderECommerceData->loyalty_points)->isNotEmpty();
    }

    public function hasLoyaltyPointsForCart(): bool
    {
        if (CommonFunctions::compareFloatNumbers($this->orderECommerceData->cart_loyalty_point_amount ?? 0.00, 0.00)) {
            return false;
        }

        return $this->orderECommerceData->cart_loyalty_points > 0;
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

    public function getPaymentAmount(): float
    {
        return (float) $this->orderECommerceData->payment_amount;
    }

    public function getHappenedAtFormat(): Carbon
    {
        if ($this->orderECommerceData->happened_at) {
            /** @var Carbon */
            return Carbon::createFromFormat('Y-m-d H:i:s', $this->orderECommerceData->happened_at);
        }

        return now();
    }

    public function checkProductPriceWithType(Product $product, array $orderItem): void
    {
        if ($product->type_id !== ProductTypes::REGULAR_PRODUCT->value) {
            return;
        }

        if ($this->isPriceAttached($orderItem)) {
            return;
        }

        $orderMismatchMessage = 'Price is not provided for the product with the name ' . $product->name;
        CommonFunctions::addMismatchOrAbort($this->orderMismatches, $orderMismatchMessage);
    }

    public function isPriceAttached(array $orderItem): bool
    {
        return array_key_exists('price', $orderItem) && $orderItem['price'];
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
            abort(412, $this->orderECommerceData->member_id . ' specified member is deleted or in-active. ');
        }
    }

    public function hasOrderItems(): bool
    {
        return $this->orderItems->isNotEmpty();
    }

    public function isMemberAttached(): bool
    {
        return $this->orderECommerceData->member_id && null !== $this->orderECommerceData->member_id;
    }

    public function hasMemberDetails(): bool
    {
        return collect($this->orderECommerceData->member_details)->isNotEmpty();
    }

    public function hasVoucher(): bool
    {
        return (bool) $this->orderECommerceData->voucher_number;
    }

    public function isRoundOffValueProvided(): bool
    {
        return null !== $this->orderECommerceData->order_round_off_amount;
    }

    public function checkRecordsExists(): void
    {
        $this->checkProducts();

        $this->checkPaymentTypes();
    }

    public function checkProducts(): void
    {
        if ($this->products->where('status', false)->isNotEmpty()) {
            abort(412, 'Some of the products are archived.');
        }

        if ($this->products->where('type_id', ProductTypes::REGULAR_PRODUCT->value)->isEmpty()) {
            abort(412, 'Only Regular Product Are Allowed.');
        }

        if ($this->products->count() === $this->orderItems->pluck('id')->unique()->count()) {
            return;
        }

        if (! $this->orderItems->has('id')) {
            return;
        }

        abort(412, 'Some of the products are not in our records.');
    }

    public function checkPaymentTypes(): void
    {
        if (null === $this->orderECommerceData->payment_type_id && null === $this->orderECommerceData->payment_amount) {
            return;
        }

        $paymentId = (int) $this->orderECommerceData->payment_type_id;

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentType = $paymentTypeQueries->getById($paymentId, $this->companyId);

        if (! $paymentType->getStatus()) {
            abort(412, 'Some of the payment types are inactive.');
        }

        if ($this->orderECommerceData->member_id) {
            return;
        }

        if ($this->orderECommerceData->member_details) {
            return;
        }

        if (! $paymentType->getIsMemberRequired()) {
            return;
        }

        abort(412, 'Member is required for one of the selected payment types.');
    }

    public function getItemSubtotal(array $item): float
    {
        if ($this->isPriceAttached($item)) {
            return CommonFunctions::numberFormat($item['price'] * $item['quantity']);
        }

        return 0.0;
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

    public function getCartSubtotal(): float
    {
        $cartSubtotal = $this->orderItems->sum(fn ($orderItem): float => $this->getItemSubtotal($orderItem));

        return CommonFunctions::numberFormat($cartSubtotal);
    }

    public function checkValidMember(
        SaleChannel $saleChannel,
        OrderECommerceData $orderECommerceData
    ): OrderECommerceData {
        if ($saleChannel->type_id->value === SaleChannelTypes::ECOMMERCE->value && $orderECommerceData->member_id) {
            $memberChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
            $memberChannelReference = $memberChannelReferenceQueries->getByExternalMemberIdAndSaleChannelId(
                $orderECommerceData->member_id,
                $saleChannel->id
            );

            $orderECommerceData->member_id = $memberChannelReference?->member_id;
        }

        if ($orderECommerceData->member_id) {
            $memberQueries = resolve(MemberQueries::class);
            $member = $memberQueries->memberExistsById($saleChannel->getCompanyId(), $orderECommerceData->member_id);
            if (! $member) {
                abort(412, 'Member not exists.');
            }
        }

        return $orderECommerceData;
    }

    public function orderItemMapping(SaleChannel $saleChannel, OrderECommerceData $orderECommerceData): array
    {
        $orderItems = collect($orderECommerceData->order_items);
        $upcs = $orderItems->pluck('upc')->filter()->toArray();

        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getByIdsWithBrandAndCategoriesForEcommerce(
            $upcs,
            $orderItems->pluck('id')->filter()->toArray(),
            $saleChannel->getCompanyId()
        );

        $requestProductCount = $orderItems->pluck('upc')->filter()->unique()->count() + $orderItems->pluck(
            'id'
        )->filter()->unique()->count();

        if ($products->count() !== $requestProductCount) {
            abort(412, 'Please provide the correct product UPC or ID.');
        }

        if ($products->isEmpty()) {
            abort(412, 'Please provide the correct product UPC or ID.');
        }

        if ([] !== $upcs) {
            $orderItems->transform(function (array $orderItem) use ($products): array {
                $orderItem['id'] = $products->firstWhere('upc', $orderItem['upc'])?->id;

                return $orderItem;
            });
        }

        return [$orderItems, $products];
    }

    public function hasCartPromotion(): bool
    {
        return (bool) $this->orderECommerceData->cart_promotion_id;
    }

    public function hasDreamPrice(array $cartItem): bool
    {
        return (
            array_key_exists('dream_price_id', $cartItem) && null !== $cartItem['dream_price_id']
        ) && (
            array_key_exists('dream_price_amount', $cartItem) && null !== $cartItem['dream_price_amount']
        );
    }

    public function getCartSubtotalByDiscountApplicableType(float $cartSubtotal): float
    {
        if ($this->company->discount_applicable_type === DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value) {
            return $cartSubtotal;
        }

        return $this->getCartSubtotal();
    }
}
