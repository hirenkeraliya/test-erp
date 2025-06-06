<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentRefund\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\CashierGroup\Enums\PermissionChecks;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\Company\Enums\BookingPaymentRefundTypes;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\BookingPayment;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class CheckBookingPaymentRequestDetailsService
{
    public Collection $bookingPaymentMismatches;

    public function checkBillReferenceNumber(?string $billReferenceNumber, Company $company): void
    {
        if (! $company->is_bill_reference_number_mandatory) {
            return;
        }

        if (null !== $billReferenceNumber) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->bookingPaymentMismatches,
            'Bill reference number is required while booking payment store.'
        );
    }

    public function checkOfflineId(string $offlineId, int $companyId): void
    {
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        if ($bookingPaymentQueries->doesOfflineIdExist($offlineId, $companyId)) {
            abort(412, 'The selected offline ID is already in use.');
        }
    }

    public function checkBoxProduct(?array $products, int $companyId): void
    {
        if (! $products) {
            return;
        }

        $boxProductQueries = resolve(BoxProductQueries::class);
        foreach ($products as $product) {
            $boxProductId = $product['box_product_id'] ?? $product['product_bundle_id'] ?? null;
            if ($boxProductId) {
                $productBox = $boxProductQueries->findBoxByIdAndProductId(
                    (int) $boxProductId,
                    (int) $product['product_id']
                );

                if (null === $productBox) {
                    $productQueries = resolve(ProductQueries::class);
                    $product = $productQueries->getById((int) $product['product_id'], $companyId);
                    abort(
                        412,
                        'Product Box Not Found. The product box associated with '.$product->name. ' was not found.'
                    );
                }
            }
        }
    }

    public function checkPromoters(
        BookingPaymentData|BookingPaymentProductData $bookingPaymentData,
        int $companyId
    ): void {
        $promoterIds = collect($bookingPaymentData->promoter_ids)->unique()->filter();
        $productsPromoterIds = collect($bookingPaymentData->products)->pluck('promoter_ids')->collapse()
            ->unique()
            ->filter();

        $allPromoterIds = $promoterIds->merge($productsPromoterIds)->unique()->toArray();
        $promoterQueries = resolve(PromoterQueries::class);

        $allPromotersExist = $promoterQueries->doAllPromotersExist($allPromoterIds, $companyId);

        if (! $allPromotersExist) {
            abort(412, 'Some of the promoters are not available in our records.');
        }
    }

    public function checkAuthorization(): void
    {
        if (! Gate::allows(PermissionChecks::CASHIER->name, PermissionTypes::BOOKING_PAYMENT)) {
            CommonFunctions::addMismatchOrAbort(
                $this->bookingPaymentMismatches,
                'The cashier is not authorized to perform this transaction.'
            );
        }
    }

    public function setDetails(): void
    {
        $this->bookingPaymentMismatches = collect([]);
    }

    public function checkMemberDetails(int|string|null $memberId, ?array $member, int $companyId): void
    {
        $memberQueries = resolve(MemberQueries::class);

        if (($memberId && null !== $memberId) && collect($member)->isNotEmpty()) {
            abort(412, 'Please provide either member id or member details, not both.');
        }

        if (null === $memberId && collect($member)->isEmpty()) {
            abort(412, 'Member is required for the booking payment.');
        }

        if ($member) {
            if (! array_key_exists('first_name', $member) || ! $member['first_name']) {
                abort(412, 'First name is required');
            }

            if (! array_key_exists('mobile_number', $member) || ! $member['mobile_number']) {
                abort(412, 'mobile number is required');
            }

            $checkMember = $memberQueries->checkMemberByMobileNumber($companyId, $member['mobile_number']);
            if (null !== $checkMember) {
                if ($checkMember->status === Status::ACTIVE->value) {
                    return;
                }

                CommonFunctions::addMismatchOrAbort(
                    $this->bookingPaymentMismatches,
                    $checkMember['id'] . ' specified member is deleted and currently in-Active.'
                );
            }
        }

        if ($memberId && null !== $memberId) {
            $checkMember = $memberQueries->memberExistsById($companyId, (int) $memberId);
            if (! $checkMember) {
                return;
            }

            if ($checkMember->status === Status::ACTIVE->value) {
                return;
            }

            CommonFunctions::addMismatchOrAbort(
                $this->bookingPaymentMismatches,
                $memberId . ' specified member is deleted and currently in-Active. '
            );
        }
    }

    public function checkStatusIsRefunded(int $bookingStatus): void
    {
        if ($bookingStatus === BookingPaymentStatuses::REFUNDED->value) {
            CommonFunctions::addMismatchOrAbort($this->bookingPaymentMismatches, 'Booking Payment already refunded');
        }
    }

    public function checkPaymentType(int $paymentTypeId): void
    {
        if (StaticPaymentTypes::CREDIT_NOTE->value === $paymentTypeId) {
            abort(412, 'Payment type cannot be credit note payment.');
        }

        if (StaticPaymentTypes::BOOKING_PAYMENT->value === $paymentTypeId) {
            abort(412, 'Payment type cannot be booking payment.');
        }

        if (StaticPaymentTypes::LOYALTY_POINT->value === $paymentTypeId) {
            abort(412, 'Payment type cannot be loyalty point.');
        }

        if (StaticPaymentTypes::GIFT_CARD->value === $paymentTypeId) {
            abort(412, 'Payment type cannot be gift card.');
        }
    }

    public function checkStatusIsActive(BookingPayment $bookingPayment): void
    {
        if ($bookingPayment->getStatus() !== BookingPaymentStatuses::ACTIVE->value) {
            CommonFunctions::addMismatchOrAbort(
                $this->bookingPaymentMismatches,
                'Action cannot be performed. This booking payment is not active.'
            );
        }
    }

    public function checkProductsExist(int $companyId, ?array $products): void
    {
        if (! $products) {
            return;
        }

        if (collect($products)->pluck('product_id')->filter()->isEmpty()) {
            return;
        }

        $productQueries = resolve(ProductQueries::class);
        $doAllProductsExist = $productQueries->doAllProductsExist(
            $companyId,
            array_unique(array_column($products, 'product_id'))
        );

        if (! $doAllProductsExist) {
            abort(412, 'Some of the products are not available in our records.');
        }
    }

    public function checksBeforeRefund(
        BookingPayment $bookingPayment,
        BookingPaymentRefundData $bookingPaymentRefundData,
        Company $company
    ): void {
        $this->checkBookingPaymentRefundAmount($bookingPayment, $bookingPaymentRefundData, $company);
        $this->checkRefundPayments($bookingPaymentRefundData, $company->id);
        $this->checkStatusIsActive($bookingPayment);
        $this->checkStatusIsRefunded($bookingPayment->getStatus());
    }

    public function checkRefundPayments(BookingPaymentRefundData $bookingPaymentRefundData, int $companyId): void
    {
        $this->checkPaymentType($bookingPaymentRefundData->payment_type_id);

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentType = $paymentTypeQueries->getById($bookingPaymentRefundData->payment_type_id, $companyId);

        if (! $paymentType->is_available_for_refund) {
            CommonFunctions::addMismatchOrAbort(
                $this->bookingPaymentMismatches,
                'Specified payment type is not available for refund.'
            );
        }
    }

    public function checkBookingPaymentRefundAmount(
        BookingPayment $bookingPayment,
        BookingPaymentRefundData $bookingPaymentRefundData,
        Company $company
    ): void {
        if ($bookingPayment->available_amount < $bookingPaymentRefundData->amount) {
            $saleMismatchMessage = 'Specified refund amount ' . $bookingPaymentRefundData->amount . ' is more than available amount of the booking payment ' . $bookingPayment->available_amount;
            CommonFunctions::addMismatchOrAbort($this->bookingPaymentMismatches, $saleMismatchMessage);
        }

        if ($company->booking_payment_refund_type === BookingPaymentRefundTypes::PARTIALLY->value) {
            return;
        }

        if (
            CommonFunctions::compareFloatNumbers(
                (float) $bookingPayment->available_amount,
                $bookingPaymentRefundData->amount
            )
        ) {
            return;
        }

        $saleMismatchMessage = 'You cannot use booking payment partially. kindly use full booking payment. Specified payment amount is ' . $bookingPaymentRefundData->amount . ' and available booking payment amount is ' . $bookingPayment->available_amount;
        CommonFunctions::addMismatchOrAbort($this->bookingPaymentMismatches, $saleMismatchMessage);
    }

    public function checkStoreManagerAuthorized(BookingPaymentData $bookingPaymentData, int $companyId): void
    {
        if (! ($bookingPaymentData->store_manager_id && $bookingPaymentData->store_manager_passcode)) {
            abort(412, 'Store Manager id & passcode is required to authorized booking payment');
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getByIdWithEmployee(
            $bookingPaymentData->store_manager_id,
            $companyId
        );

        if (! $storeManager) {
            abort(412, 'Specified Store Manager does not correspond with our records.');
        }

        $this->checkStoreManagerAuthorizationCode($bookingPaymentData);

        /** @var Employee $employee */
        $employee = $storeManager->employee;
        if (! $employee->getStatus()) {
            CommonFunctions::addMismatchOrAbort(
                $this->bookingPaymentMismatches,
                'Specified Store Manager : ' . $employee->getFullName() . ' account is inactive. Please contact admin.'
            );
        }

        if (! $storeManager->passcode) {
            return;
        }

        if ($storeManager->passcode === $bookingPaymentData->store_manager_passcode) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->bookingPaymentMismatches,
            'The Store Manager provided passcode for authorization does not correspond with our records.'
        );
    }

    public function checkStoreManagerAuthorizationCode(BookingPaymentData $bookingPaymentData): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $this->bookingPaymentMismatches,
            (int) $bookingPaymentData->store_manager_id,
            $bookingPaymentData->store_manager_authorization_code,
            $bookingPaymentData->happened_at
        );
    }

    public function handleBookingPaymentChecks(
        BookingPayment $bookingPayment,
        BookingPaymentData|BookingPaymentProductData $bookingPaymentProductData,
        int $companyId
    ): void {
        $this->setDetails();
        $this->checkAuthorization();
        $this->checkPromoters($bookingPaymentProductData, $companyId);
        $this->checkProductsExist($companyId, $bookingPaymentProductData->products);
        $this->checkStatusIsActive($bookingPayment);
        $this->checkBoxProduct($bookingPaymentProductData->products, $companyId);
    }

    public function validateBookingPaymentRequestAndMember(
        Company $company,
        Location $location,
        Cashier $cashier,
        BookingPaymentData $bookingPaymentData,
    ): void {
        $this->validateBookingPaymentRequest($company, $bookingPaymentData);

        $this->validateMemberDetails($cashier, $bookingPaymentData, $company->getKey(), $location->getKey());

        /** @var CompanySetting $companySetting */
        $companySetting = $company->companySetting;

        $checkCompanySettingService = resolve(CheckCompanySettingService::class);
        $checkCompanySettingService->setDetails($companySetting);
        $checkCompanySettingService->checkBookingPaymentSettings(
            $bookingPaymentData,
            $this->bookingPaymentMismatches
        );
    }

    public function validateBookingPaymentRequest(
        Company $company,
        BookingPaymentData $bookingPaymentData,
    ): void {
        $companyId = (int) $company->getKey();

        $this->setDetails();
        $this->checkAuthorization();
        $this->checkOfflineId($bookingPaymentData->offline_id, $companyId);
        $this->checkBillReferenceNumber($bookingPaymentData->bill_reference_number, $company);
        $this->checkPromoters($bookingPaymentData, $companyId);
        $this->checkMemberDetails($bookingPaymentData->member_id, $bookingPaymentData->member, $companyId);
        $this->checkProductsExist($companyId, $bookingPaymentData->products);
        $this->checkStoreManagerAuthorized($bookingPaymentData, $companyId);
        $this->checkBoxProduct($bookingPaymentData->products, $companyId);
    }

    public function prepareAndCheckPaymentStatus(
        BookingPayment $bookingPayment,
        BookingPaymentTopUpData $bookingPaymentTopUpData,
        Company $company
    ): void {
        $this->setDetails();
        $this->checkAuthorization();
        $this->checkStatusIsActive($bookingPayment);

        /** @var CompanySetting $companySetting */
        $companySetting = $company->companySetting;

        $checkCompanySettingService = resolve(CheckCompanySettingService::class);
        $checkCompanySettingService->setDetails($companySetting);
        $checkCompanySettingService->checkBookingPaymentSettings(
            $bookingPaymentTopUpData,
            $this->bookingPaymentMismatches
        );
    }

    public function prepareAndAuthorizeRefund(
        BookingPayment $bookingPayment,
        Company $company,
        BookingPaymentRefundData $bookingPaymentRefundData
    ): void {
        $this->setDetails();
        $this->checkAuthorization();
        $this->checksBeforeRefund($bookingPayment, $bookingPaymentRefundData, $company);
        $this->checkPaymentCurrency($bookingPaymentRefundData, $company);
    }

    public function checkPaymentCurrency(BookingPaymentRefundData $bookingPaymentRefundData, Company $company): void
    {
        $currencyIds = [];
        $currencyRates = [];

        foreach ($company->countries as $country) {
            $currencyIds[] = $country->currency?->id;
            $currencyRates[] = CommonFunctions::numberFormat((float) $country->currency?->currencyRate?->rate);
        }

        if (isset($bookingPaymentRefundData->currency_id, $bookingPaymentRefundData->current_currency_rate, $bookingPaymentRefundData->currency_amount)) {
            if (! in_array($bookingPaymentRefundData->currency_id, $currencyIds)) {
                $saleMismatchMessage = 'Payment currency id ' . $bookingPaymentRefundData->currency_id . ' is not available in this company.';
                CommonFunctions::addMismatchOrAbort($this->bookingPaymentMismatches, $saleMismatchMessage);
            }

            if (! in_array($bookingPaymentRefundData->current_currency_rate, $currencyRates)) {
                $saleMismatchMessage = 'Payment currency rate ' . $bookingPaymentRefundData->current_currency_rate . ' does not match with the actual currency rate of ' . implode(
                    ', ',
                    $currencyRates
                ) . ' for the currency id ' . $bookingPaymentRefundData->currency_id;
                CommonFunctions::addMismatchOrAbort($this->bookingPaymentMismatches, $saleMismatchMessage);
            }

            $currencyAmount = CommonFunctions::numberFormat(
                CommonFunctions::numberFormat(
                    $bookingPaymentRefundData->currency_amount
                ) / CommonFunctions::numberFormat($bookingPaymentRefundData->current_currency_rate)
            );

            if (! CommonFunctions::compareFloatNumbers($currencyAmount, $bookingPaymentRefundData->amount)) {
                $saleMismatchMessage = 'Payment amount ' . $bookingPaymentRefundData->amount . ' does not match with the actual currency amount of ' . $currencyAmount . '.';
                CommonFunctions::addMismatchOrAbort($this->bookingPaymentMismatches, $saleMismatchMessage);
            }
        }
    }

    private function validateMemberDetails(
        Cashier $cashier,
        BookingPaymentData $bookingPaymentData,
        int $companyId,
        int $locationId
    ): void {
        if (! $bookingPaymentData->member) {
            return;
        }

        $memberDetails = $bookingPaymentData->member;

        $memberService = resolve(MemberService::class);
        $bookingPaymentData->member_id = $memberService->getMemberIdFromDetails($memberDetails, $companyId);

        if (! $bookingPaymentData->member_id) {
            $memberService->checkRequiredMemberColumns($memberDetails, $companyId);

            $bookingPaymentData->member_id = $memberService->addNewMember(
                $cashier,
                $memberDetails,
                $companyId,
                $locationId,
                MemberChannelEnum::POS->value
            );
        }
    }
}
