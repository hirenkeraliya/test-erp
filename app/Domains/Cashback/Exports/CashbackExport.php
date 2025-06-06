<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Exports;

use App\CommonFunctions;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Cashback;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashbackExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $cashbacks
    ) {
    }

    public function collection(): Collection
    {
        return $this->cashbacks->map(function (Cashback $cashback): array {
            $endDate = Carbon::createFromFormat('Y-m-d', $cashback->end_date);
            $startDate = Carbon::createFromFormat('Y-m-d', $cashback->start_date);

            $saleDiscount = $cashback->saleDiscountCashback;
            $saleItemDiscount = $cashback->saleItemDiscountCashback;

            $totalUsedCounts = $saleDiscount->count() + $saleItemDiscount->count();

            $totalDiscountAmount = CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            );

            return [
                'exclude_by_type' => ExcludeByTypes::getFormattedCaseName($cashback->exclude_by_type),
                'name' => $cashback->name,
                'discount_type' => DiscountTypes::getFormattedCaseName($cashback->discount_type_id),
                'discount_value' => $cashback->discount_value ?: 'N/A',
                'minimum_spend_amount' => $cashback->minimum_spend_amount,
                'usage' => 'Count: '.$totalUsedCounts. ', Amount: ' .$totalDiscountAmount,
                'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
                'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Exclude By Type',
            'Name',
            'Discount Type',
            'Discount Value',
            'Minimum Spend',
            'Usage',
            'Start Date',
            'End Date',
        ];
    }
}
