<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\CommonFunctions;
use App\Domains\Voucher\DataObjects\BirthdayVoucherData;
use App\Domains\Voucher\VoucherQueries;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayVoucherCheckRequestService
{
    /**
     * @var Collection<mixed, mixed>|mixed
     */
    public $birthdayVoucherMismatches;

    public function setDetails(): void
    {
        $this->birthdayVoucherMismatches = collect([]);
    }

    public function checkRequestDetails(
        BirthdayVoucherData $birthdayVoucherData,
        VoucherConfiguration $voucherConfiguration,
        int $companyId
    ): void {
        $voucherQueries = resolve(VoucherQueries::class);
        $doVoucherNumbersExist = $voucherQueries->doVoucherNumbersExist([$birthdayVoucherData->number], $companyId);

        if ($doVoucherNumbersExist) {
            abort(
                412,
                'The specified voucher number is already in our records. Please provide a unique voucher number.'
            );
        }

        if ($birthdayVoucherData->discount_type !== $voucherConfiguration->discount_type) {
            CommonFunctions::addMismatchOrAbort(
                $this->birthdayVoucherMismatches,
                'The specified voucher ' . $birthdayVoucherData->number . ' does not match the discount type specified in the respective voucher configuration.'
            );
        }

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $birthdayVoucherData->happened_at);
        $happenedAt = $happenedAtFormat->format('Y-m-d');

        $actualExpiryDate = null;
        if ($voucherConfiguration->validity_days > 0) {
            $actualExpiryDate = $happenedAtFormat->addDays($voucherConfiguration->validity_days)->format('Y-m-d');
        }

        if ($voucherConfiguration->start_date > $happenedAt || $voucherConfiguration->end_date < $happenedAt) {
            CommonFunctions::addMismatchOrAbort(
                $this->birthdayVoucherMismatches,
                'The specified voucher configuration is available only between ' . $voucherConfiguration->start_date . ' and ' . $voucherConfiguration->end_date . ' However, the requested date is ' . $happenedAt . '.'
            );
        }

        if ($actualExpiryDate !== $birthdayVoucherData->expired_at) {
            CommonFunctions::addMismatchOrAbort(
                $this->birthdayVoucherMismatches,
                'The specified voucher expiry date is not valid. The actual expiry date for the voucher is ' . $actualExpiryDate . ' which matches the given expiry date ' . $birthdayVoucherData->expired_at
            );
        }

        if (
            ! CommonFunctions::compareFloatNumbers(
                (float) $voucherConfiguration->use_minimum_spend_amount,
                $birthdayVoucherData->minimum_spend_amount
            )
        ) {
            CommonFunctions::addMismatchOrAbort(
                $this->birthdayVoucherMismatches,
                'The specified minimum spend amount is not valid. The actual minimum spend amount is ' . $voucherConfiguration->use_minimum_spend_amount . ' while the requested minimum spend amount is ' . $birthdayVoucherData->minimum_spend_amount
            );
        }
    }
}
