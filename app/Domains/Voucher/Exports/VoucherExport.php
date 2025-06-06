<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Location;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoucherExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $vouchers,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->vouchers->map(function (Voucher $voucher): array {
            $member = $voucher->member;
            /** @var ?Location $location */
            $location = $voucher->createdByLocation;

            /** @var VoucherConfiguration $voucherConfiguration */
            $voucherConfiguration = $voucher->voucherConfiguration;

            /** @var Carbon $createdAt */
            $createdAt = $voucher->created_at;
            /** @var Carbon|string $cancelledAt */
            $cancelledAt = '';
            if ($voucher->cancelled_at) {
                /** @var Carbon $cancelledAtFormat */
                $cancelledAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $voucher->cancelled_at);
                $cancelledAt = $cancelledAtFormat->format('d-m-Y h:i:s A');
            }

            /** @var Carbon|string $usedAt */
            $usedAt = '';
            if ($voucher->used_at) {
                /** @var Carbon $usedAtFormat */
                $usedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $voucher->used_at);
                $usedAt = $usedAtFormat->format('d-m-Y h:i:s A');
            }

            /** @var Carbon|string $expiryDate */
            $expiryDate = '';
            if ($voucher->expiry_date) {
                /** @var Carbon $expiryDateFormat */
                $expiryDateFormat = Carbon::createFromFormat('Y-m-d', $voucher->expiry_date);
                $expiryDate = $expiryDateFormat->format('d-m-Y');
            }

            $voucherData = [
                'number' => $voucher->number,
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'member' => null !== $member ? $member->getFullName() : 'Walk in member',
                'location' => $location instanceof Location ? $location->name : 'N/A',
                'voucher_type' => VoucherTypes::getFormattedCaseName($voucherConfiguration->voucher_type),
                'minimum_spend_amount' => CommonFunctions::currencyFormat((float) $voucher->minimum_spend_amount),
                'discount' => CommonFunctions::currencyFormat($voucher->getDiscountValue($voucher->discount_type)),
                'status' => VoucherStatusTypes::getFormattedCaseName($voucher->status),
                'expiry_date' => $expiryDate,
                'used_at' => $usedAt,
                'cancelled_at' => $cancelledAt,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($voucherData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
