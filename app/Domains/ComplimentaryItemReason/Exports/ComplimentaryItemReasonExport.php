<?php

declare(strict_types=1);

namespace App\Domains\ComplimentaryItemReason\Exports;

use App\CommonFunctions;
use App\Models\ComplimentaryItemReason;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComplimentaryItemReasonExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $complimentaryItemReasons
    ) {
    }

    public function collection(): Collection
    {
        return $this->complimentaryItemReasons->map(function (ComplimentaryItemReason $complimentaryItemReason): array {
            $saleDiscount = $complimentaryItemReason->saleDiscountComplimentaryItemReason;
            $saleItemDiscount = $complimentaryItemReason->saleItemDiscountComplimentaryItemReason;
            $totalUsedCounts = $saleDiscount->count() + $saleItemDiscount->count();
            $totalDiscountAmount = CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            );

            return [
                'reason' => $complimentaryItemReason->reason,
                'usage' => 'Count: '.$totalUsedCounts. ', Amount: ' .$totalDiscountAmount,
            ];
        });
    }

    public function headings(): array
    {
        return ['Reason', 'Usage'];
    }
}
