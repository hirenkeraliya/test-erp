<?php

declare(strict_types=1);

namespace App\Domains\Employee\Resources;

use App\Domains\Employee\Enums\JobTypes;
use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Employee $employee */
        $employee = $this;

        /** @var Designation $designation */
        $designation = $employee->designation;

        /** @var Carbon $registeredAt */
        $registeredAt = $employee->created_at;

        return [
            'id' => $employee->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'mobile_number' => $employee->mobile_number,
            'home_contact' => $employee->home_contact,
            'address_line_1' => $employee->address_line_1,
            'address_line_2' => $employee->address_line_2,
            'city' => $employee->city,
            'area_code' => $employee->area_code,
            'date_of_joining' => $employee->date_of_joining,
            'primary_contact_name' => $employee->primary_contact_name,
            'primary_contact_phone' => $employee->primary_contact_phone,
            'staff_id' => $employee->staff_id,
            'membership_id' => $employee->membership_id,
            'ic_number' => $employee->ic_number,
            'group' => $employee->employeeGroup ? [
                'id' => $employee->employeeGroup->id,
                'name' => $employee->employeeGroup->name,
                'code' => $employee->employeeGroup->code,
            ] : null,
            'used_limit' => $employee->employeeGroup ? $this->getEmployeePurchaseLimit($employee) : null,
            'spent_till_now' => (float) $employee->member?->spent_till_now,
            'total_loyalty_points' => (int) $employee->member?->loyalty_points,
            'total_redeemed_points' => (int) $employee->member?->total_redeemed_points,
            'total_earned_points' => (int) $employee->member?->total_earned_points,
            'total_expired_points' => (int) $employee->member?->total_expired_points,
            'total_sales' => (int) $employee->member?->total_sales,
            'photo_url' => $employee->getDiskBasedFirstMediaUrl('photo'),
            'registered_at' => $registeredAt->format('Y-m-d H:i:s'),
            'status' => [
                'id' => (int) $employee->status,
                'employee_status' => (int) $employee->status !== 0 ? 'Active' : 'Inactive',
            ],
            'job_type' => $employee->job_type ? [
                'id' => $employee->job_type,
                'name' => JobTypes::getFormattedCaseName($employee->job_type),
                'key' => JobTypes::getCaseNameByValue($employee->job_type),
            ] : null,
            'designation' => [
                'id' => $designation->id,
                'name' => $designation->name,
                'code' => $designation->code,
            ],
        ];
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
