<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Exports;

use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\SaleTarget;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleTargetExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $saleTargets
    ) {
    }

    public function collection(): Collection
    {
        return $this->saleTargets->map(fn (SaleTarget $saleTarget): array => [
            'name' => $saleTarget->name,
            'amount' => $saleTarget->amount,
            'amount_type' => SaleTargetAmountTypes::getFormattedCaseName((int) $saleTarget->amount_type),
            'percentage' => $saleTarget->percentage ?? 'N/A',
            'target_type' => TargetType::getFormattedCaseName((int) $saleTarget->target_type),
            'time_interval_type' => TimeIntervalType::getFormattedCaseName((int) $saleTarget->time_interval_type),
            'status' => $saleTarget->status ? 'Active' : 'Inactive',
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Amount', 'Amount Type', 'Percentage', 'Target Type', 'Time Interval Type', 'Status'];
    }
}
