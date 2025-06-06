<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\MemberGroup\DataPreparer\MemberGroupDataPreparer;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\Member;
use App\Models\MemberAddress;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosMemberEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Member $member */
        $member = $this->resource;

        /** @var Carbon $registeredAt */
        $registeredAt = $member->created_at;

        /** @var Collection $vouchers */
        $vouchers = $member->vouchers;

        /** @var Employee $employee */
        $employee = $member->employee;

        /** @var Collection $memberAddresses */
        $memberAddresses = $member->memberAddresses;

        /** @var MemberAddress|null $primaryAddress */
        $primaryAddress = $member->primaryMemberAddress;

        $userDataPreparer = resolve(UserDataPreparer::class);
        $memberGroupDataPreparer = resolve(MemberGroupDataPreparer::class);

        return [
            'id' => $member->id,
            'employee_id' => $member->employee_id,
            'employee_group' => $employee->employeeGroup ? [
                'id' => $employee->employeeGroup->id,
                'name' => $employee->employeeGroup->name,
                'code' => $employee->employeeGroup->code,
            ] : null,
            'used_limit' => $employee->employeeGroup ? $this->getEmployeePurchaseLimit($employee) : null,
            'job_type' => $employee->job_type,
            'ic_number' => $employee->ic_number,
            'staff_id' => $employee->staff_id,
            'status_details' => Status::getFormattedArrayForPos($member->status),
            'type_details' => $member->type_id ? [
                'id' => $member->type_id,
                'name' => Types::getFormattedCaseName($member->type_id),
                'key' => Types::getCaseNameByValue($member->type_id),
            ] : null,
            'title_details' => $member->title_id ? [
                'id' => $member->title_id,
                'name' => Titles::getFormattedCaseName($member->title_id),
                'key' => Titles::getCaseNameByValue($member->title_id),
            ] : null,
            'gender_details' => $member->gender_id ? [
                'id' => $member->gender_id,
                'name' => Genders::getFormattedCaseName($member->gender_id),
                'key' => Genders::getCaseNameByValue($member->gender_id),
            ] : null,
            'race_details' => $member->race_id ? [
                'id' => $member->race_id,
                'name' => Races::getFormattedCaseName($member->race_id),
                'key' => Races::getCaseNameByValue($member->race_id),
            ] : null,
            'group' => $memberGroupDataPreparer->getMemberGroup($member),
            'groups' => $memberGroupDataPreparer->getMemberGroups($member),
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'address_line_1' => $primaryAddress ? $primaryAddress->address_line_1 : null,
            'address_line_2' => $primaryAddress ? $primaryAddress->address_line_2 : null,
            'city' => $primaryAddress ? $primaryAddress->city : null,
            'area_code' => $primaryAddress ? $primaryAddress->area_code : null,
            'date_of_birth' => $member->date_of_birth,
            'total_orders' => (float) $member->total_orders,
            'spent_till_now' => (float) $member->spent_till_now,
            'last_purchase_date' => $member->last_purchase_date ? $member->last_purchase_date->format(
                'Y-m-d h:i:s'
            ) : null,
            'company_name' => $member->company_name,
            'company_registration_number' => $member->company_registration_number,
            'company_tax_number' => $member->company_tax_number,
            'company_phone' => $member->company_phone,
            'notes' => $member->notes,
            'photo_url' => $member->getDiskBasedFirstMediaUrl('photo'),
            'total_loyalty_points' => (int) $member->loyalty_points,
            'total_redeemed_points' => (int) $member->total_redeemed_points,
            'total_earned_points' => (int) $member->total_earned_points,
            'total_expired_points' => (int) $member->total_expired_points,
            'total_sales' => (int) $member->total_sales,
            'membership_id' => $member->membership_id,
            'registered_at' => $registeredAt->format('Y-m-d H:i:s'),
            'vouchers' => $this->getVouchersDetails($vouchers),
            'member_addresses' => $userDataPreparer->getMemberAddressDetails($memberAddresses),
            'card_number' => $member->card_number,
            'voucher_generated' => $this->checkVoucherIsGenerated($member->date_of_birth, $member->birthdayVoucher),
        ];
    }

    private function checkVoucherIsGenerated(?string $memberDateOfBirth, ?Voucher $voucher): bool
    {
        if (! $memberDateOfBirth) {
            return false;
        }

        if (! $voucher instanceof Voucher) {
            return false;
        }

        /** @var Carbon $dateOfBirth */
        $dateOfBirth = Carbon::createFromFormat('Y-m-d', $memberDateOfBirth);

        /** @var Carbon $voucherCreatedAt */
        $voucherCreatedAt = $voucher->created_at;

        return $voucherCreatedAt->format('m') === $dateOfBirth->format('m') &&
                $voucherCreatedAt->format('Y') === now()->format('Y');
    }

    private function getVouchersDetails(Collection $vouchers): Collection
    {
        return $vouchers->map(function ($voucher): array {
            /** @var VoucherConfiguration $voucherConfiguration */
            $voucherConfiguration = $voucher->voucherConfiguration;

            /** @var Collection $excludeProducts */
            $excludeProducts = $voucherConfiguration->products;

            /** @var Collection $excludeCategories */
            $excludeCategories = $voucherConfiguration->categories;

            return [
                'id' => $voucher->id,
                'voucher_configuration_id' => $voucher->voucher_configuration_id,
                'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
                'voucher_type' => VoucherTypes::getCaseNameByValue($voucherConfiguration->voucher_type),
                'number' => $voucher->number,
                'minimum_spend_amount' => $voucher->minimum_spend_amount,
                'percentage' => $voucher->percentage,
                'flat_amount' => $voucher->flat_amount,
                'used_at' => $voucher->used_at,
                'expiry_date' => $voucher->expiry_date,
                'dream_price_applicable' => $voucher->dream_price_applicable,
                'item_wise_promotion_applicable' => $voucher->item_wise_promotion_applicable,
                'cart_wide_promotion_applicable' => $voucher->cart_wide_promotion_applicable,
                'exclude_products' => $excludeProducts->isNotEmpty() ? $excludeProducts->pluck('id')->toArray() : null,
                'exclude_categories' => $excludeCategories->isNotEmpty() ? $excludeCategories->pluck(
                    'id'
                )->toArray() : null,
                'transactions' => $voucher->getVoucherTransactions(),
            ];
        });
    }

    public function getEmployeePurchaseLimit(self|Employee $employeeData): float
    {
        /** @var Employee $employee */
        $employee = $employeeData;

        /** @var EmployeeGroup $employeeGroup */
        $employeeGroup = $employee->employeeGroup;
        $employeePurchaseLimit = $employeeGroup->item_purchase_limit;

        if ($employeePurchaseLimit <= 0) {
            return 0.00;
        }

        if ((int) $employeeGroup->purchase_limit_type_id === PurchaseLimitTypes::BY_ITEMS->value) {
            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_WEEK->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByWeekLimit($employeeGroup->limit_reset);

                return $this->getTotalQuantities($previousDate, $currentDate, $employee->id);
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_MONTH->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByMonthLimit($employeeGroup->limit_reset);

                return $this->getTotalQuantities($previousDate, $currentDate, $employee->id);
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_DAYS->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByDaysLimit($employeeGroup->limit_reset);

                return $this->getTotalQuantities($previousDate, $currentDate, $employee->id);
            }
        }

        if ((int) $employeeGroup->purchase_limit_type_id === PurchaseLimitTypes::BY_AMOUNT->value) {
            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_WEEK->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByWeekLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                return (float) $sales->sum('total_amount_paid') - (float) $saleReturns->sum('total_price_paid');
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_MONTH->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByMonthLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                return (float) $sales->sum('total_amount_paid') - (float) $saleReturns->sum('total_price_paid');
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_DAYS->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByDaysLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                return (float) $sales->sum('total_amount_paid') - (float) $saleReturns->sum('total_price_paid');
            }
        }

        if ((int) $employeeGroup->purchase_limit_type_id === PurchaseLimitTypes::BY_SALE->value) {
            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_WEEK->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByWeekLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                return (float) ($sales->count() - $saleReturns->count());
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_MONTH->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByMonthLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                return (float) ($sales->count() - $saleReturns->count());
            }

            if ($employeeGroup->limit_reset_type_id === LimitResetTypes::BY_DAYS->value) {
                [$previousDate, $currentDate] = $this->preparedDateRangeByDaysLimit($employeeGroup->limit_reset);

                [$sales, $saleReturns] = $this->fetchSalesAndSaleReturns($previousDate, $currentDate, $employee->id);

                return (float) ($sales->count() - $saleReturns->count());
            }
        }

        return 0.00;
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

    private function getTotalQuantities(string $previousDate, string $currentDate, int $employeeId): float
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

        return (float) ($saleTotalQuantitiesPurchased - $saleReturnTotalQuantities);
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
}
