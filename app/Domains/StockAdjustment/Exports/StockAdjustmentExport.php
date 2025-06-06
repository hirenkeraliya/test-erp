<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Exports;

use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockAdjustmentExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockAdjustments
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockAdjustments->map(function (StockAdjustment $stockAdjustment): array {
            /** @var Carbon|string $adjustmentDate */
            $adjustmentDate = 'N/A';

            if ($stockAdjustment->adjustment_date) {
                /** @var Carbon $adjustmentDateFormat */
                $adjustmentDateFormat = Carbon::createFromFormat('Y-m-d', $stockAdjustment->adjustment_date);
                $adjustmentDate = $adjustmentDateFormat->format('d-m-Y');
            }

            return [
                'adjustment_date' => $adjustmentDate,
                'reason' => $stockAdjustment->reason,
                'approved_by' => $stockAdjustment->employee?->first_name . ' ' . $stockAdjustment->employee?->last_name,
                'type' => StockAdjustmentTypes::tryFrom($stockAdjustment->type_id)?->name,
            ];
        });
    }

    public function headings(): array
    {
        return ['Adjustment Date', 'Reason', 'Approved By', 'Type'];
    }
}
