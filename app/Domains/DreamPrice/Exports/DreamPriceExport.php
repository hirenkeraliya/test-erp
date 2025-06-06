<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Exports;

use App\CommonFunctions;
use App\Domains\Common\Enums\Statuses;
use App\Models\DreamPrice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DreamPriceExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $dreamPrices
    ) {
    }

    public function collection(): Collection
    {
        return $this->dreamPrices->map(function (DreamPrice $dreamPrice): array {
            /** @var Carbon $startDateFormat */
            $startDateFormat = Carbon::createFromFormat('Y-m-d', $dreamPrice->start_date);
            /** @var Carbon $endDateFormat */
            $endDateFormat = Carbon::createFromFormat('Y-m-d', $dreamPrice->end_date);
            $startDate = $startDateFormat->format('d-m-Y');
            $endDate = $endDateFormat->format('d-m-Y');

            $saleDiscount = $dreamPrice->saleDiscountDreamPrice;
            $saleItemDiscount = $dreamPrice->saleItemDiscountDreamPrice;
            $totalUsedCounts = $saleDiscount->count() + $saleItemDiscount->count();
            $totalDiscountAmount = CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            );

            return [
                'name' => $dreamPrice->name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'dream_price_products_count' => (int) $dreamPrice->dreamPriceProducts->count(),
                'usage' => 'Count: '.$totalUsedCounts. ', Amount: ' .$totalDiscountAmount,
                'status' => Statuses::getFormattedCaseName((int) $dreamPrice->status),
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'Start Date', 'End Date', 'Dream Price Products Count', 'Usage', 'Status'];
    }
}
