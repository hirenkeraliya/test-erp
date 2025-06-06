<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Exports;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoucherConfigurationExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $voucherConfigurations
    ) {
    }

    public function collection(): Collection
    {
        return $this->voucherConfigurations->map(function (VoucherConfiguration $voucherConfiguration): array {
            /** @var Carbon $startDateFormat */
            $startDateFormat = Carbon::createFromFormat('Y-m-d', $voucherConfiguration->start_date);
            /** @var Carbon $endDateFormat */
            $endDateFormat = Carbon::createFromFormat('Y-m-d', $voucherConfiguration->end_date);
            $startDate = $startDateFormat->format('d-m-Y');
            $endDate = $endDateFormat->format('d-m-Y');

            $vouchers = $voucherConfiguration->vouchers;

            $voucherConfigurationService = resolve(VoucherConfigurationService::class);

            [$totalUsedCounts, $totalDiscountAmount] = $voucherConfigurationService::calculateTotalCountsAndAmount(
                $vouchers
            );

            return [
                'restricted_by_type' => RestrictedByTypes::getFormattedCaseName(
                    $voucherConfiguration->restricted_by_type
                ),
                'voucher_type' => VoucherTypes::getFormattedCaseName($voucherConfiguration->voucher_type),
                'discount_type' => DiscountTypes::getFormattedCaseName($voucherConfiguration->discount_type),
                'get_value' => $voucherConfiguration->get_value,
                'usage' => 'Count: '.$totalUsedCounts. ', Amount: ' .$totalDiscountAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $voucherConfiguration->status ? 'Active' : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Restricted By Type',
            'Voucher Type',
            'Discount Type',
            'Get Value',
            'Usage',
            'Start Date',
            'End Date',
            'Status',
        ];
    }
}
