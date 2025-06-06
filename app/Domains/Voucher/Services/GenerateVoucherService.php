<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Sale;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class GenerateVoucherService
{
    public CheckSaleDetailsService $checkSaleDetailsService;

    public Collection $voucherConfigurations;

    public function setDetails(CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $this->checkSaleDetailsService = $checkSaleDetailsService;

        if (! $checkSaleDetailsService->saleData->vouchers instanceof DataCollection) {
            return;
        }

        $vouchers = collect($checkSaleDetailsService->saleData->vouchers->toArray());
        $voucherConfigurationIds = $vouchers->pluck('voucher_configuration_id')->unique()->filter()->toArray();
        $this->voucherConfigurations = $this->getVoucherConfigurations($voucherConfigurationIds);
    }

    public function getVoucherConfigurations(array $voucherConfigurationIds): Collection
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        return $voucherConfigurationQueries->getByIds(
            $voucherConfigurationIds,
            $this->checkSaleDetailsService->companyId
        );
    }

    public function checkVouchers(float $subtotal): void
    {
        if (! $this->checkSaleDetailsService->saleData->vouchers instanceof DataCollection) {
            return;
        }

        if ($this->checkSaleDetailsService->isLayawaySale()) {
            abort(412, 'Voucher cannot be generated for Layaway Sales.');
        }

        if ($this->checkSaleDetailsService->isCreditSale()) {
            abort(412, 'Voucher cannot be generated for Credit Sales.');
        }

        $vouchers = collect($this->checkSaleDetailsService->saleData->vouchers->toArray());
        $voucherNumbers = $vouchers->pluck('number')->unique()->filter()->toArray();
        $voucherQueries = resolve(VoucherQueries::class);

        $doVoucherNumbersExist = $voucherQueries->doVoucherNumbersExist(
            $voucherNumbers,
            $this->checkSaleDetailsService->companyId
        );

        if ($doVoucherNumbersExist) {
            abort(
                412,
                'Some of the voucher numbers are already in our records. Please provide distinct voucher numbers.'
            );
        }

        if ($this->checkSaleDetailsService->saleData->employee_id) {
            abort(412, 'Voucher cannot be generated for the employees.');
        }

        foreach ($this->checkSaleDetailsService->saleData->vouchers as $voucher) {
            /** @var GenerateVoucherData $voucher */
            $voucherData = $voucher;
            $voucherConfiguration = $this->voucherConfigurations->firstWhere(
                'id',
                $voucherData->voucher_configuration_id
            );

            if (null === $voucherConfiguration) {
                abort(412, 'The specified voucher configuration is not available in our records.');
            }

            if (false === $voucherConfiguration->status) {
                $saleMismatchMessage = 'Specified voucher configuration is not active.';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            if (VoucherTypes::BIRTHDAY_VOUCHER->value === $voucherConfiguration->voucher_type) {
                abort(
                    412,
                    'The specified voucher is a birthday voucher. You cannot generate birthday vouchers from the POS application.'
                );
            }

            if (VoucherTypes::WELCOME_MEMBER->value === $voucherConfiguration->voucher_type) {
                abort(
                    412,
                    'The specified voucher is a welcome member voucher. You cannot generate welcome member vouchers from the POS application.'
                );
            }

            if (
                RestrictedByTypes::MEMBER_ONLY->value === $voucherConfiguration->restricted_by_type &&
                (! $this->checkSaleDetailsService->isMemberAttached()
                && ! $this->checkSaleDetailsService->hasMemberDetails())
            ) {
                abort(412, 'The member is required for the specified voucher configuration.');
            }

            if (
                RestrictedByTypes::NON_MEMBER_ONLY->value === $voucherConfiguration->restricted_by_type &&
                ($this->checkSaleDetailsService->isMemberAttached()
                || $this->checkSaleDetailsService->hasMemberDetails())
            ) {
                abort(
                    412,
                    'This voucher configuration can only be used when there are no members attached to the sale.'
                );
            }

            if ($voucherData->discount_type !== $voucherConfiguration->discount_type) {
                $saleMismatchMessage = 'The discount type specified for voucher ' . $voucherData->number . ' does not match the respective voucher configuration.';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            if (VoucherTypes::MULTIPLE_VOUCHER->value === $voucherConfiguration->voucher_type) {
                if (
                    $voucher->discount_type === DiscountTypes::PERCENTAGE->value &&
                    null !== $voucher->percentage && ! CommonFunctions::compareFloatNumbers(
                        $voucher->percentage,
                        (float) $voucherConfiguration->get_value
                    )
                ) {
                    $saleMismatchMessage = 'There is a mismatch in the voucher percentage discount. The actual percentage discount for the voucher is ' . $voucherConfiguration->get_value . ' whereas the given percentage discount is ' . $voucher->percentage;
                    CommonFunctions::addMismatchOrAbort(
                        $this->checkSaleDetailsService->saleMismatches,
                        $saleMismatchMessage
                    );
                }

                if (
                    $voucher->discount_type === DiscountTypes::FLAT->value &&
                    null !== $voucher->flat_amount && ! CommonFunctions::compareFloatNumbers(
                        $voucher->flat_amount,
                        (float) $voucherConfiguration->get_value
                    )
                ) {
                    $saleMismatchMessage = 'There is a mismatch in the flat amount discount for the voucher. The actual flat amount for the voucher is ' . $voucherConfiguration->get_value . ' while the given flat amount is ' . $voucher->flat_amount;
                    CommonFunctions::addMismatchOrAbort(
                        $this->checkSaleDetailsService->saleMismatches,
                        $saleMismatchMessage
                    );
                }
            }

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $this->checkSaleDetailsService->saleData->happened_at
            );

            $happenedAt = $happenedAtFormat->format('Y-m-d');
            $actualExpiryDate = null;
            if ($voucherConfiguration->validity_days > 0) {
                $actualExpiryDate = $happenedAtFormat->addDays($voucherConfiguration->validity_days)->format('Y-m-d');
            }

            if ($voucherConfiguration->start_date > $happenedAt || $voucherConfiguration->end_date < $happenedAt) {
                $saleMismatchMessage = 'The specified voucher configuration is available only between ' . $voucherConfiguration->start_date . ' and ' . $voucherConfiguration->end_date . '. The requested date is ' . $happenedAt . 'is not within the valid range.';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            if ($actualExpiryDate !== $voucher->expired_at) {
                $saleMismatchMessage = 'The specified voucher expiry date is not valid. The actual expiry date for the voucher is ' . $actualExpiryDate . ' while the given expiry date is ' . $voucher->expired_at;
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            if (
                ! CommonFunctions::compareFloatNumbers(
                    (float) $voucherConfiguration->use_minimum_spend_amount,
                    $voucher->minimum_spend_amount
                )
            ) {
                $saleMismatchMessage = 'The specified minimum spend amount is not valid. The actual minimum spend amount is ' . $voucherConfiguration->use_minimum_spend_amount . ' while the requested minimum spend amount is ' . $voucher->minimum_spend_amount;
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            $excludedSubtotal = $subtotal - $this->getExcludeAmountForVoucher($voucherConfiguration);

            if ($voucherConfiguration->voucher_type === VoucherTypes::TIER_VOUCHER->value) {
                $voucherTier = $this->getVoucherTier($voucherConfiguration, $excludedSubtotal);

                if (! $voucherTier instanceof VoucherConfigurationTier) {
                    $saleMismatchMessage = 'The specified voucher configuration is not valid. The sale amount after exclusions is ' . $excludedSubtotal . ' Based on that amount, no tier is available as of.';
                    CommonFunctions::addMismatchOrAbort(
                        $this->checkSaleDetailsService->saleMismatches,
                        $saleMismatchMessage
                    );
                }

                if (
                    $voucher->discount_type === DiscountTypes::PERCENTAGE->value &&
                    null !== $voucher->percentage && ! CommonFunctions::compareFloatNumbers(
                        $voucher->percentage,
                        (float) $voucherTier?->get_value
                    )
                ) {
                    $saleMismatchMessage = 'There is a mismatch in the voucher percentage discount. The actual percentage discount for the voucher is ' . $voucherTier?->get_value . ' whereas the given percentage discount is ' . $voucher->percentage;
                    CommonFunctions::addMismatchOrAbort(
                        $this->checkSaleDetailsService->saleMismatches,
                        $saleMismatchMessage
                    );
                }

                if (
                    $voucher->discount_type === DiscountTypes::FLAT->value &&
                    null !== $voucher->flat_amount && ! CommonFunctions::compareFloatNumbers(
                        $voucher->flat_amount,
                        (float) $voucherTier?->get_value
                    )
                ) {
                    $saleMismatchMessage = 'There is a mismatch in the flat amount discount for the voucher. The actual flat amount for the voucher is ' . $voucherTier?->get_value . ' while the given flat amount is ' . $voucher->flat_amount;
                    CommonFunctions::addMismatchOrAbort(
                        $this->checkSaleDetailsService->saleMismatches,
                        $saleMismatchMessage
                    );
                }

                continue;
            }

            if ($voucherConfiguration->issue_minimum_spend_amount > $excludedSubtotal) {
                $saleMismatchMessage = 'The specified voucher configuration is not valid. The sale amount after exclusions is ' . $excludedSubtotal . ' and the minimum spend amount for this voucher is ' . $voucherConfiguration->issue_minimum_spend_amount;
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }
        }
    }

    public function getExcludeAmountForVoucher(VoucherConfiguration $voucherConfiguration): float
    {
        if (ExcludeByTypes::NONE->value === $voucherConfiguration->exclude_by_type) {
            return 0.00;
        }

        $cartItems = $this->checkSaleDetailsService->cartItems;
        if ($voucherConfiguration->exclude_by_type === ExcludeByTypes::CATEGORIES->value) {
            $amountToExclude = 0.00;

            foreach ($cartItems as $cartItem) {
                $product = $this->checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
                $cartItemCategoryIds = $product->categories->pluck('id');
                $voucherExcludeCategoryIds = $voucherConfiguration->categories->pluck('id');

                $isValidProductAccordingToCategories = $voucherExcludeCategoryIds->intersect($cartItemCategoryIds);

                if ($isValidProductAccordingToCategories->isNotEmpty()) {
                    $amountToExclude += $cartItem['price'] * $cartItem['quantity'];
                }
            }

            return $amountToExclude;
        }

        $amountToExclude = 0.00;
        $voucherExcludeProductIds = $voucherConfiguration->products->pluck('id');

        foreach ($cartItems as $cartItem) {
            if ($voucherExcludeProductIds->contains($cartItem['id'])) {
                $amountToExclude += $cartItem['price'] * $cartItem['quantity'];
            }
        }

        return $amountToExclude;
    }

    public function getVoucherTier(
        VoucherConfiguration $voucherConfiguration,
        float $subtotal
    ): ?VoucherConfigurationTier {
        $voucherConfigurationTiers = $voucherConfiguration->voucherConfigurationTiers->sortByDesc(
            'minimum_spend_amount'
        );

        foreach ($voucherConfigurationTiers as $voucherConfigurationTier) {
            if ($voucherConfigurationTier->minimum_spend_amount > $subtotal) {
                continue;
            }

            if ($voucherConfigurationTier->maximum_spend_amount < $subtotal) {
                continue;
            }

            return $voucherConfigurationTier;
        }

        return null;
    }

    public function saveVouchers(Sale $sale): void
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        if (! $this->checkSaleDetailsService->saleData->vouchers instanceof DataCollection) {
            return;
        }

        $voucherQueries = resolve(VoucherQueries::class);
        $voucherTransactionQueries = resolve(VoucherTransactionQueries::class);
        foreach ($this->checkSaleDetailsService->saleData->vouchers as $voucher) {
            /** @var GenerateVoucherData $voucher */
            $voucherData = $voucher;

            /** @var VoucherConfiguration $voucherConfiguration */
            $voucherConfiguration = $this->voucherConfigurations->firstWhere(
                'id',
                $voucherData->voucher_configuration_id
            );

            $expiryDate = null;
            if ($voucherData->expired_at) {
                /** @var Carbon $expiryDate */
                $expiryDate = Carbon::createFromFormat('Y-m-d', $voucherData->expired_at);
            }

            $voucher = $voucherQueries->addNew(
                $voucherConfiguration,
                $voucherData->percentage ?: (float) $voucherData->flat_amount,
                $voucherData->discount_type,
                $expiryDate,
                $sale->member_id,
                $voucherData->number,
                $sale->getKey(),
                $counter->getLocationId(),
            );

            $voucherTransactionQueries->addNew(
                $voucher->id,
                VoucherTransactionActionTypes::CREATED->value,
                $this->checkSaleDetailsService->saleData->happened_at,
                $sale->getKey(),
                $counter->getLocationId()
            );
        }
    }
}
